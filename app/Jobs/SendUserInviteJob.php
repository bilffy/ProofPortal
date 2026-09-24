<?php

namespace App\Jobs;

use App\Mail\UserInviteMail;
use App\Models\Email;
use App\Models\Template;
use App\Models\User;
use App\Models\Status;
use App\Models\UserInviteToken;
use App\Services\EmailValidationService;
use App\Services\Proofing\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\SentMessage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\TextPart;

class SendUserInviteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected int $senderId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, int $senderId)
    {
        $this->user = $user;
        $this->senderId = $senderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(StatusService $statusService, EmailValidationService $emailValidationService)
    {
        $sender = User::find($this->senderId);

        // Check the address with SendGrid's Email Address Validation API before
        // doing anything else - this key can't send mail, read bounces, etc.,
        // so this is the only way we have to catch a typo'd domain (e.g.
        // "gmial.com") up front instead of after a real send attempt.
        $validation = $emailValidationService->validate($this->user->email);

        if ($validation['deliverable'] === false) {
            Log::warning('[invite-debug] SendGrid flagged invite email as invalid - not sending', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'verdict' => $validation['verdict'],
            ]);

            $this->recordInvalidInviteEmail($sender, $statusService, $validation['verdict']);

            return;
        }

        $token = Password::broker('invites')->createToken($this->user);

        $setupUrl = route('account.setup.create', [
            'token' => $token,
            'email' => $this->user->getHashedIdAttribute()
        ], true);        
        
        // Set the user status to invited
        $this->user->status = User::STATUS_INVITED;

        $status = Status::where('status_external_name', 'invited')->first();
        $this->user->active_status_id = $status->id;
        
        $this->user->save();

        // Send the invite email
        $sentMessage = Mail::to($this->user->email)->send(new UserInviteMail($this->user, $this->senderId, $setupUrl));

        Log::info('[invite-debug] Mail::send() returned', [
            'user_id' => $this->user->id,
            'sentMessage_type' => is_object($sentMessage) ? get_class($sentMessage) : gettype($sentMessage),
            'is_SentMessage' => $sentMessage instanceof SentMessage,
        ]);

        if ($sentMessage instanceof SentMessage) {
            $this->recordSentInviteEmail($sentMessage, $sender, $statusService);
        } else {
            Log::warning('[invite-debug] Skipped recordSentInviteEmail because sentMessage was not a SentMessage instance', [
                'user_id' => $this->user->id,
            ]);
        }
    }

    /**
     * Record an `emails` audit row for an invite that SendGrid flagged as
     * undeliverable, so it shows up in the emails table as a failure
     * (smtp_code/smtp_message) instead of silently never happening. Mirrors
     * recordSentInviteEmail()'s payload shape, minus the actual send.
     */
    protected function recordInvalidInviteEmail(?User $sender, StatusService $statusService, ?string $verdict): void
    {
        try {
            $template = Template::where('template_name', 'user_added')->first();

            $schoolId = null;
            $alphacode = null;
            $schoolKey = null;

            if ($this->user->isSchoolLevel()) {
                $school = $this->user->getSchool();
                $alphacode = $this->user->getFranchise()?->alphacode;
                $schoolId = $school?->id ?: null;
                $schoolKey = $school?->schoolkey ?: null;
            } elseif ($this->user->isFranchiseLevel()) {
                $alphacode = $this->user->getFranchise()?->alphacode;
            }

            $payload = [
                'generated_from_user_id' => $this->senderId,
                'alphacode' => $alphacode,
                'school_id' => $schoolId,
                'ts_schoolkey' => $schoolKey,
                'sentdate' => now(),
                'email_from' => $sender?->email,
                'email_to' => $this->user->email,
                'email_content' => null,
                // 550 (mailbox unavailable) is the standard SMTP code for an
                // invalid/non-existent recipient - matches what a real send
                // attempt would have bounced with. smtp_message is capped at
                // 25 chars in the emails table, so keep this generic; the
                // actual SendGrid verdict is in the log line above.
                'smtp_code' => 550,
                'smtp_message' => 'Invalid email address',
                'template_id' => $template?->id,
                'status_id' => $statusService->error,
            ];

            $email = Email::create($payload);

            Log::info('[invite-debug] Recorded invalid-email emails row', [
                'user_id' => $this->user->id,
                'email_row_id' => $email->id,
                'verdict' => $verdict,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to record invalid invite email', [
                'user_id' => $this->user->id,
                'sender_id' => $this->senderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record an `emails` audit row for the invite email that was just sent,
     * mirroring the records kept for proof_start / proof_warning / proof_due emails.
     */
    protected function recordSentInviteEmail(SentMessage $sentMessage, ?User $sender, StatusService $statusService): void
    {
        Log::info('[invite-debug] recordSentInviteEmail starting', [
            'user_id' => $this->user->id,
            'sender_id' => $this->senderId,
        ]);

        try {
            $originalMessage = $sentMessage->getSymfonySentMessage()->getOriginalMessage();
            $emlContent = $this->buildSinglePartHtmlEml($originalMessage);

            if ($emlContent === null) {
                Log::warning('[invite-debug] Falling back to raw multipart EML - could not extract HTML body for single-part rebuild', [
                    'user_id' => $this->user->id,
                ]);
                $emlContent = MessageConverter::toEmail($originalMessage)->toString();
            }

            $template = Template::where('template_name', 'user_added')->first();

            if (!$template) {
                Log::warning('[invite-debug] No template row found with template_name = user_added; template_id will be stored as null', [
                    'user_id' => $this->user->id,
                ]);
            }

            $schoolId = null;
            $alphacode = null;
            $schoolKey = null;

            if ($this->user->isSchoolLevel()) {
                // School admin, photo coordinator or teacher - assign the school they were invited to.
                // `User` has no getSchoolKey() - the key lives on the School model as `schoolkey`
                // (mapped to the emails table's `ts_schoolkey` column).
                $school = $this->user->getSchool();
                $alphacode = $this->user->getFranchise()?->alphacode;
                $schoolId = $school?->id ?: null;
                $schoolKey = $school?->schoolkey ?: null;
            } elseif ($this->user->isFranchiseLevel()) {
                $alphacode = $this->user->getFranchise()?->alphacode;
            }

            $payload = [
                'generated_from_user_id' => $this->senderId,
                'alphacode' => $alphacode,
                'school_id' => $schoolId,
                'ts_schoolkey' => $schoolKey,
                'sentdate' => now(),
                'email_from' => $sender?->email,
                'email_to' => $this->user->email,
                'email_content' => $emlContent,
                'smtp_code' => 250,
                'smtp_message' => 'Sent Successfully',
                'template_id' => $template?->id,
                'status_id' => $statusService->emailSent,
            ];

            Log::info('[invite-debug] About to insert emails row', [
                'user_id' => $this->user->id,
                'payload' => collect($payload)->except('email_content')->all(),
            ]);

            $email = Email::create($payload);

            Log::info('[invite-debug] Inserted emails row successfully', [
                'user_id' => $this->user->id,
                'email_row_id' => $email->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to record sent invite email', [
                'user_id' => $this->user->id,
                'sender_id' => $this->senderId,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Rebuild the sent message as a single-part, base64-encoded HTML email
     * for storage in the `emails` table, instead of the raw
     * multipart/alternative + quoted-printable message Laravel's Markdown
     * mailables produce by default.
     *
     * This is the exact same shape EmailService::generateEmail() already
     * builds for the proofing invitation/reminder emails - built there for
     * this same reason: the quoted-printable multipart form was showing raw
     * "=20" / "=3D" escape sequences instead of rendering once an email row
     * was resent. We feed it the HTML that was already rendered for the
     * actual sent message rather than re-rendering the Markdown view again.
     *
     * Returns null (letting the caller fall back to the raw EML) if the
     * original message isn't a Symfony Email or has no HTML body to rebuild from.
     */
    protected function buildSinglePartHtmlEml($originalMessage): ?string
    {
        if (!$originalMessage instanceof SymfonyEmail) {
            return null;
        }

        $htmlBody = $originalMessage->getHtmlBody();
        if (!$htmlBody) {
            return null;
        }

        $subject = (string) $originalMessage->getSubject();
        $htmlPart = new TextPart((string) $htmlBody, 'utf-8', 'html', 'base64');

        $rebuilt = (new SymfonyEmail())
            ->from(new Address('noreply@msp.com.au', 'MSP Portal - Do Not Reply'))
            ->to(new Address($this->user->email, $this->user->name))
            ->subject($subject)
            ->setBody($htmlPart)
            ->date(now());

        return MessageConverter::toEmail($rebuilt)->toString();
    }
}