<?php

namespace App\Http\Controllers\Proofing;

use App\Helpers\SchoolContextHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Email;
use App\Models\Franchise;
use App\Models\Job;
use App\Models\Order;
use App\Models\School;
use App\Models\Season;
use App\Models\Status;
use App\Models\Template;
use App\Repositories\ReportRepository;
use App\Services\Proofing\StatusService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
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
        $jobs = $this->franchiseJobs();
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
        $orderingSchools = $this->orderingSchoolsQuery($franchise)->get();

        return view('proofing.franchise.emails.index', [
            'jobs' => $jobs,
            'seasonList' => $seasonList,
            'user' => new UserResource($user),
            'invitationSchools' => $invitationSchools,
            'orderingSchools' => $orderingSchools,
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
        // The school's name goes in brackets after the season (school_id first, school key as a fallback).
        $schoolName = $job->school_id
            ? School::where('id', $job->school_id)->value('name')
            : ($job->ts_schoolkey ? School::where('schoolkey', $job->ts_schoolkey)->value('name') : null);
        if (!empty($schoolName)) {
            $jobTitle .= ' (' . $schoolName . ')';
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

        $messages = $this->applySort($query, $request, $orderColumn, $direction)->paginate($limit, ['*'], 'page', $page);

        return view('proofing.franchise.emails._results', [
            'messages' => $messages,
            'sortBy' => $request->input('email_filter_order_by', 'created_at'),
            'sortDir' => $request->input('email_filter_order_direction', 'descending'),
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

        $messages = $this->applySort($query, $request, $orderColumn, $direction)->paginate($limit, ['*'], 'page', $page);

        return view('proofing.franchise.emails._results', [
            'messages' => $messages,
            'sortBy' => $request->input('email_filter_order_by', 'created_at'),
            'sortDir' => $request->input('email_filter_order_direction', 'descending'),
        ]);
    }

    /**
     * Show the requestor copies of Artwork/Print order emails for a selected school.
     */
    public function showOrdering(string $schoolId)
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

        $emails = $this->schoolOrderingEmailsQuery($school)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('proofing.franchise.emails.ordering-show', [
            'school' => $school,
            'emails' => $emails,
            'schoolIdEncrypted' => $schoolId,
            'user' => new UserResource(Auth::user()),
        ]);
    }

    /**
     * Filter a school's order emails (AJAX).
     */
    public function filterOrdering(Request $request)
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

        $query = $this->schoolOrderingEmailsQuery($school);

        $this->applyKeywordFilter($query, $filter);

        $messages = $this->applySort($query, $request, $orderColumn, $direction)->paginate($limit, ['*'], 'page', $page);

        return view('proofing.franchise.emails._results', [
            'messages' => $messages,
            'showOrder' => true,
            'sortBy' => $request->input('email_filter_order_by', 'created_at'),
            'sortDir' => $request->input('email_filter_order_direction', 'descending'),
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
        return app(\App\Services\Proofing\EmailBodyExtractor::class)->extract($eml);
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

        // Ordering emails carry a single-use, expiring artwork download link.
        // Rebuild the body with a fresh link so a late resend isn't a dead one
        // (does nothing for other emails, and never blocks the resend).
        app(\App\Services\Proofing\EmailService::class)->refreshOrderEmailForResend($email);

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

    /**
     * Synced proofing jobs (show_proofing = 1, not deleted / not found; archived
     * jobs are included) of the Franchise user's franchise, matched on
     * jobs.ts_account_id = franchises.ts_account_id, not just the jobs
     * assigned to that user. Other users keep the jobs assigned to them.
     */
    protected function franchiseJobs(): Collection
    {
        $user = Auth::user();
        $franchise = $user?->isFranchiseLevel() ? $user->getFranchise() : null;

        if (!$franchise) {
            return $this->reportRepository->getSchoolsIds();
        }

        if ($franchise->ts_account_id === null) {
            return collect();
        }

        return Job::query()
            ->join('status', 'status.id', '=', 'jobs.jobsync_status_id')
            ->where('jobs.ts_account_id', $franchise->ts_account_id)
            ->where('jobs.show_proofing', 1)
            ->where('jobs.jobsync_status_id', $this->statusService->sync)
            ->whereNotIn('jobs.job_status_id', [
                $this->statusService->tnjNotFound,
                $this->statusService->deleted,
            ])
            ->select(
                'jobs.id',
                'jobs.ts_job_id',
                'jobs.ts_jobname',
                'jobs.ts_season_id',
                'jobs.school_id',
                'jobs.ts_schoolkey',
                'status.status_external_name as sync_status'
            )
            ->orderBy('jobs.ts_jobname', 'asc')
            ->get();
    }

    protected function resolveAccessibleJob(int $tsJobId): ?Job
    {
        $accessibleIds = $this->franchiseJobs()->pluck('ts_job_id')->map(fn ($id) => (int) $id);

        if (!$accessibleIds->contains($tsJobId)) {
            return null;
        }

        return Job::withoutGlobalScopes()
            ->where('ts_job_id', $tsJobId)
            ->first(['id', 'ts_job_id', 'ts_jobkey', 'ts_jobname', 'ts_season_id', 'school_id', 'ts_schoolkey']);
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
     * Order the email list by the clicked column. The status and order number columns are sorted
     * by what is shown (status name / order number) rather than by the foreign key.
     */
    protected function applySort(Builder $query, Request $request, string $orderColumn, string $direction): Builder
    {
        switch ($request->input('email_filter_order_by')) {
            case 'status_name':
                $query->orderBy(
                    Status::select('status_external_name')->whereColumn('status.id', 'emails.status_id'),
                    $direction
                );
                break;
            case 'order_number':
                $query->orderBy(
                    Order::select('order_number')->whereColumn('orders.id', 'emails.order_id'),
                    $direction
                );
                break;
            default:
                $query->orderBy($orderColumn, $direction);
        }

        return $query->orderBy('emails.id', $direction);
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

    /**
     * Schools belonging to this franchise that have at least one order email
     * sent to the requestor, for the "Ordering" tab's school list.
     */
    protected function orderingSchoolsQuery(?Franchise $franchise): Builder|BelongsToMany
    {
        if (!$franchise) {
            return School::query()->whereRaw('1 = 0');
        }

        $schoolIdsWithOrders = Email::where('order_recipient', Email::ORDER_RECIPIENT_REQUESTOR)
            ->whereHas('order.product', fn ($q) => $q->where('show_in_emails', true))
            ->whereNotNull('school_id')
            ->distinct()
            ->pluck('school_id');

        return $franchise->schools()
            ->whereIn('schools.id', $schoolIdsWithOrders)
            ->orderBy('schools.name');
    }

    /**
     * Requestor copies of order emails for one school (the print team's copy
     * is internal and not listed here).
     */
    protected function schoolOrderingEmailsQuery(School $school): Builder
    {
        return Email::query()
            ->with(['status', 'template', 'order'])
            ->where('order_recipient', Email::ORDER_RECIPIENT_REQUESTOR)
            ->whereHas('order.product', fn ($q) => $q->where('show_in_emails', true))
            ->where('school_id', $school->id);
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
