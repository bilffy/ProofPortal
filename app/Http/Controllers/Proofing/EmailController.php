<?php

namespace App\Http\Controllers\Proofing;

use App\Helpers\SchoolContextHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Email;
use App\Models\Franchise;
use App\Models\Job;
use App\Models\School;
use App\Models\Season;
use App\Models\Template;
use App\Repositories\ReportRepository;
use App\Services\Proofing\StatusService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class EmailController extends Controller
{
    public function __construct(
        protected ReportRepository $reportRepository,
        protected StatusService $statusService
    ) {
    }

    /**
     * Emails landing page (Franchise only): synced jobs for the "Proofing" tab,
     * plus the schools that have received a user-invitation email for the
     * "User Invitation" tab. No school needs to be selected to reach this page.
     */
    public function index()
    {
        // Franchise-only landing page - block it while a school context is active,
        // same as /franchise-dashboard (see FranchiseDashboard::mount()).
        if (SchoolContextHelper::isSchoolContext()) {
            return redirect()->route('photography.configure-new');
        }

        $user = Auth::user();
        $jobs = $this->reportRepository->getSchoolsIds();
        $seasonList = Season::orderBy('code', 'asc')->pluck('code', 'ts_season_id')->toArray();

        // Prefer school_id (schools.id) when the job has one; ts_schoolkey is a
        // legacy key that isn't guaranteed unique across schools (e.g. DEMO),
        // so it's only used as a fallback when school_id is null.
        $schoolNamesById = School::whereIn('id', $jobs->pluck('school_id')->filter()->unique()->values())
            ->pluck('name', 'id');

        $schoolNamesByKey = School::whereIn('schoolkey', $jobs->pluck('ts_schoolkey')->filter()->unique()->values())
            ->pluck('name', 'schoolkey');

        $jobs->each(function ($job) use ($schoolNamesById, $schoolNamesByKey) {
            $job->school_name = $job->school_id
                ? ($schoolNamesById[$job->school_id] ?? null)
                : ($job->ts_schoolkey ? ($schoolNamesByKey[$job->ts_schoolkey] ?? null) : null);
        });

        $franchise = $user->getFranchise();
        $invitationSchools = $this->invitationSchoolsQuery($franchise)->get();

        return view('proofing.franchise.emails.index', [
            'jobs' => $jobs,
            'seasonList' => $seasonList,
            'user' => new UserResource($user),
            'invitationSchools' => $invitationSchools,
        ]);
    }

    /**
     * Show emails for a selected job.
     */
    public function show(string $tsJobId)
    {
        try {
            $decryptedTsJobId = (int) Crypt::decryptString($tsJobId);
        } catch (DecryptException $e) {
            session()->flash('error', __('Invalid job selection. Please try again.'));
            return redirect()->route('emails.index');
        }

        $job = $this->resolveAccessibleJob($decryptedTsJobId);
        if (!$job) {
            session()->flash('error', __('Job not found or you do not have access.'));
            return redirect()->route('emails.index');
        }

        $emails = Email::query()
            ->with(['status', 'template'])
            ->where('ts_jobkey', $job->ts_jobkey)
            ->orderByDesc('created_at')
            ->paginate(10);

        $user = Auth::user();
        $seasonList = Season::orderBy('code', 'asc')->pluck('code', 'ts_season_id')->toArray();
        $jobTitle = $job->ts_jobname;
        if (!empty($job->ts_season_id) && isset($seasonList[$job->ts_season_id])) {
            $jobTitle .= ' (' . $seasonList[$job->ts_season_id] . ')';
        }

        return view('proofing.franchise.emails.show', [
            'job' => $job,
            'jobTitle' => $jobTitle,
            'emails' => $emails,
            'tsJobIdEncrypted' => $tsJobId,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Filter emails for a job (AJAX).
     */
    public function filter(Request $request)
    {
        $tsJobIdEncrypted = $request->input('ts_job_id');
        try {
            $decryptedTsJobId = (int) Crypt::decryptString($tsJobIdEncrypted);
        } catch (DecryptException $e) {
            return response('<div class="alert alert-danger">Invalid job.</div>', 400);
        }

        $job = $this->resolveAccessibleJob($decryptedTsJobId);
        if (!$job) {
            return response('<div class="alert alert-danger">Job not found.</div>', 404);
        }

        [$orderColumn, $direction, $limit, $filter, $page] = $this->parseFilterInput($request);

        $query = Email::query()
            ->with(['status', 'template'])
            ->where('ts_jobkey', $job->ts_jobkey);

        $this->applyKeywordFilter($query, $filter);

        $messages = $query->orderBy($orderColumn, $direction)->paginate($limit, ['*'], 'page', $page);

        return view('proofing.franchise.emails._results', [
            'messages' => $messages,
        ]);
    }

    /**
     * Show user-invitation emails for a selected school.
     */
    public function showInvitations(string $schoolId)
    {
        try {
            $decryptedSchoolId = (int) Crypt::decryptString($schoolId);
        } catch (DecryptException $e) {
            session()->flash('error', __('Invalid school selection. Please try again.'));
            return redirect()->route('emails.index');
        }

        $school = $this->resolveAccessibleInvitationSchool($decryptedSchoolId);
        if (!$school) {
            session()->flash('error', __('School not found or you do not have access.'));
            return redirect()->route('emails.index');
        }

        $emails = $this->schoolInvitationEmailsQuery($school)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('proofing.franchise.emails.invitations-show', [
            'school' => $school,
            'emails' => $emails,
            'schoolIdEncrypted' => $schoolId,
            'user' => new UserResource(Auth::user()),
        ]);
    }

    /**
     * Filter user-invitation emails for a selected school (AJAX).
     */
    public function filterInvitations(Request $request)
    {
        $schoolIdEncrypted = $request->input('school_id');
        try {
            $decryptedSchoolId = (int) Crypt::decryptString($schoolIdEncrypted);
        } catch (DecryptException $e) {
            return response('<div class="alert alert-danger">Invalid school.</div>', 400);
        }

        $school = $this->resolveAccessibleInvitationSchool($decryptedSchoolId);
        if (!$school) {
            return response('<div class="alert alert-danger">School not found.</div>', 404);
        }

        [$orderColumn, $direction, $limit, $filter, $page] = $this->parseFilterInput($request);

        $query = $this->schoolInvitationEmailsQuery($school);

        $this->applyKeywordFilter($query, $filter);

        $messages = $query->orderBy($orderColumn, $direction)->paginate($limit, ['*'], 'page', $page);

        return view('proofing.franchise.emails._results', [
            'messages' => $messages,
        ]);
    }

    /**
     * View email details (AJAX modal).
     * Returns JSON so jQuery .html() does not strip a full <html> document.
     */
    public function view(Request $request)
    {
        $messageId = (int) $request->input('view_message_id');
        $email = $this->findAccessibleEmail($messageId);

        if (!$email) {
            return response()->json(['message' => 'Email not found.'], 404);
        }

        return response()->json([
            'to' => $email->email_to,
            'from' => $email->email_from,
            'cc' => $email->email_cc,
            'template' => $email->template->external_template_name ?? '—',
            'status' => $email->status->status_external_name
                ?? $email->status->status_internal_name
                ?? '—',
            'created' => $email->created_at ? $email->created_at->format('Y-m-d H:i') : '—',
            'sent' => $email->sentdate
                ? \Carbon\Carbon::parse($email->sentdate)->format('Y-m-d H:i')
                : '—',
            'htmlBody' => $this->extractHtmlFromEmailContent((string) $email->email_content),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * email_content is a full EML (headers + encoded body). Extract HTML for display.
     */
    protected function extractHtmlFromEmailContent(string $eml): string
    {
        if ($eml === '') {
            return '<p class="text-muted mb-0">No email content.</p>';
        }

        // Already HTML (legacy / pre-rendered rows)
        if (preg_match('/^\s*(<!DOCTYPE\s+html|<html[\s>])/i', $eml)) {
            return $eml;
        }

        $parts = preg_split("/\r\n\r\n|\n\n/", $eml, 2);
        if (count($parts) < 2) {
            return '<pre class="mb-0" style="white-space: pre-wrap;">' . e($eml) . '</pre>';
        }

        [$headers, $body] = $parts;
        $encoding = null;
        if (preg_match('/Content-Transfer-Encoding:\s*([^\r\n;]+)/i', $headers, $match)) {
            $encoding = strtolower(trim($match[1]));
        }

        $contentType = '';
        if (preg_match('/Content-Type:\s*([^\r\n;]+)/i', $headers, $match)) {
            $contentType = strtolower(trim($match[1]));
        }

        // Multipart: find the text/html section
        if (str_starts_with($contentType, 'multipart/') && preg_match('/boundary=("?)([^";\r\n]+)\1/i', $headers, $boundaryMatch)) {
            $boundary = $boundaryMatch[2];
            $segments = preg_split('/--' . preg_quote($boundary, '/') . '(?:--)?/', $body);
            foreach ($segments as $segment) {
                $segment = ltrim($segment, "\r\n");
                if ($segment === '' || !str_contains(strtolower($segment), 'text/html')) {
                    continue;
                }
                $html = $this->extractHtmlFromEmailContent($segment);
                if ($html !== '' && !str_contains($html, 'No email content')) {
                    return $html;
                }
            }
        }

        $decoded = match ($encoding) {
            'base64' => base64_decode(preg_replace('/\s+/', '', $body), true),
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };

        if ($decoded === false || $decoded === '') {
            return '<pre class="mb-0" style="white-space: pre-wrap;">' . e($eml) . '</pre>';
        }

        if (preg_match('/<html[\s>]|<!DOCTYPE\s+html/i', $decoded) || str_contains($contentType, 'text/html')) {
            return $decoded;
        }

        return '<pre class="mb-0" style="white-space: pre-wrap;">' . e($decoded) . '</pre>';
    }

    /**
     * Mark email for resend by clearing SMTP state / resetting pending status.
     * Non-schedule emails get sentdate = now(); proof schedule emails keep their sentdate.
     */
    public function resend(Request $request)
    {
        $messageId = (int) $request->input('resend_message_id');
        $email = $this->findAccessibleEmail($messageId);

        if (!$email) {
            return response('<div class="alert alert-danger">Email not found.</div>', 404);
        }

        $proofScheduleTemplates = [
            'proof_start',
            'proof_warning',
            'proof_catchup',
            'proof_due',
        ];
        $templateName = $email->template?->template_name;

        if (!in_array($templateName, $proofScheduleTemplates, true)) {
            $email->sentdate = now();
        }

        $email->smtp_code = null;
        $email->smtp_message = null;
        $email->status_id = $this->statusService->pending;
        $email->deleted_at = null;
        $email->save();

        return response(
            '<div class="alert alert-success">Email queued for resend to '
            . e($email->email_to)
            . '.</div>'
        );
    }

    protected function resolveAccessibleJob(int $tsJobId): ?Job
    {
        $accessibleIds = $this->reportRepository->getSchoolsIds()->pluck('ts_job_id')->map(fn ($id) => (int) $id);

        if (!$accessibleIds->contains($tsJobId)) {
            return null;
        }

        return Job::withoutGlobalScopes()
            ->where('ts_job_id', $tsJobId)
            ->first(['id', 'ts_job_id', 'ts_jobkey', 'ts_jobname', 'ts_season_id']);
    }

    /**
     * A school is a valid "User Invitation" destination when it belongs to the
     * current (Franchise) user's franchise - it does not need to already have
     * emails (a direct link to an empty school just shows "No emails found").
     */
    protected function resolveAccessibleInvitationSchool(int $schoolId): ?School
    {
        $franchise = Auth::user()?->getFranchise();
        if (!$franchise) {
            return null;
        }

        return $franchise->schools()->where('schools.id', $schoolId)->first();
    }

    protected function findAccessibleEmail(int $messageId): ?Email
    {
        $email = Email::with(['status', 'template'])->find($messageId);
        if (!$email) {
            return null;
        }

        if ($email->ts_jobkey) {
            $job = Job::withoutGlobalScopes()
                ->where('ts_jobkey', $email->ts_jobkey)
                ->first(['ts_job_id', 'ts_jobkey']);

            return ($job && $this->resolveAccessibleJob((int) $job->ts_job_id)) ? $email : null;
        }

        // Not job-scoped (e.g. a user-invitation email) - check franchise ownership instead.
        return $this->belongsToUsersFranchise($email) ? $email : null;
    }

    protected function userAddedTemplateId(): ?int
    {
        return Template::where('template_name', 'user_added')->value('id');
    }

    /**
     * Schools belonging to this franchise that have at least one user-invitation
     * email sent, for the "User Invitation" tab's school list.
     */
    protected function invitationSchoolsQuery(?Franchise $franchise): Builder|BelongsToMany
    {
        if (!$franchise) {
            return School::query()->whereRaw('1 = 0');
        }

        $templateId = $this->userAddedTemplateId();
        $schoolIdsWithInvites = Email::where('template_id', $templateId)
            ->whereNotNull('school_id')
            ->distinct()
            ->pluck('school_id');

        return $franchise->schools()
            ->whereIn('schools.id', $schoolIdsWithInvites)
            ->orderBy('schools.name');
    }

    /**
     * User-invitation emails (template_id belongs to `user_added`) for one school.
     */
    protected function schoolInvitationEmailsQuery(School $school): Builder
    {
        return Email::query()
            ->with(['status', 'template'])
            ->where('template_id', $this->userAddedTemplateId())
            ->where('school_id', $school->id);
    }

    /**
     * True when the given email belongs to the current (Franchise) user's franchise -
     * either sent to a franchise-level invitee (alphacode) or a school-level invitee
     * whose school belongs to this franchise (school_id).
     */
    protected function belongsToUsersFranchise(Email $email): bool
    {
        $franchise = Auth::user()?->getFranchise();
        if (!$franchise) {
            return false;
        }

        if ($email->alphacode && $email->alphacode === $franchise->alphacode) {
            return true;
        }

        if ($email->school_id) {
            return $franchise->schools()->where('schools.id', $email->school_id)->exists();
        }

        return false;
    }

    /**
     * Shared allow-listed order-by/direction/limit/keyword parsing for the
     * per-job and per-school (invitation) AJAX filters.
     *
     * @return array{0: string, 1: string, 2: int, 3: string}
     */
    protected function parseFilterInput(Request $request): array
    {
        $filter = trim((string) $request->input('email_filter_value', ''));
        $limit = (int) $request->input('email_filter_limit', 10);
        $orderBy = $request->input('email_filter_order_by', 'created_at');
        $orderDirection = $request->input('email_filter_order_direction', 'descending');
        $page = max(1, (int) $request->input('page', 1));

        $allowedOrderBy = [
            'created_at' => 'created_at',
            'started' => 'created_at',
            'completed' => 'sentdate',
            'sentdate' => 'sentdate',
            'email_to' => 'email_to',
            'email_from' => 'email_from',
            'template_id' => 'template_id',
            'status_id' => 'status_id',
            'subject' => 'email_content',
        ];
        $orderColumn = $allowedOrderBy[$orderBy] ?? 'created_at';
        $direction = $orderDirection === 'ascending' ? 'asc' : 'desc';
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 10;

        return [$orderColumn, $direction, $limit, $filter, $page];
    }

    protected function applyKeywordFilter(Builder $query, string $filter): void
    {
        if ($filter === '') {
            return;
        }

        $query->where(function ($q) use ($filter) {
            $q->where('email_to', 'like', '%' . $filter . '%')
                ->orWhere('email_from', 'like', '%' . $filter . '%')
                ->orWhere('email_content', 'like', '%' . $filter . '%');
        });
    }
}
