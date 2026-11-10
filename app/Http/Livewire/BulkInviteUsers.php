<?php

namespace App\Http\Livewire;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Helpers\RoleHelper;
use App\Helpers\SchoolContextHelper;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Status;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

class BulkInviteUsers extends Component
{
    use WithFileUploads;
    use WithPagination;

    /** Imported rows shown per page (same Livewire pagination view as /school). */
    private const ROWS_PER_PAGE = 10;

    /** Spreadsheet role values must match roles.name exactly */
    public const ROLE_LABEL_MAP = [
        RoleHelper::ROLE_SCHOOL_ADMIN => RoleHelper::ROLE_SCHOOL_ADMIN,
        RoleHelper::ROLE_PHOTO_COORDINATOR => RoleHelper::ROLE_PHOTO_COORDINATOR,
        RoleHelper::ROLE_TEACHER => RoleHelper::ROLE_TEACHER,
    ];

    public $file;

    /** @var array<int, array{firstname: string, lastname: string, email: string, role: string, send_invitation_with_proofing: bool, errors: array<int, string>}> */
    public array $rows = [];

    /** @var array<int, string> */
    public array $topErrors = [];

    public ?int $schoolId = null;

    public string $successMessage = '';

    public bool $isSubmitting = false;

    /** When false, users are assigned to the opened/current school automatically. */
    public bool $showSchoolSelector = true;

    public string $lockedSchoolName = '';

    /** @var array<int, bool> */
    public array $editingRows = [];

    protected UserService $userService;

    public function boot(UserService $userService): void
    {
        $this->userService = $userService;
    }

    public function mount(): void
    {
        $user = Auth::user();

        if (SchoolContextHelper::isSchoolContext()) {
            $school = SchoolContextHelper::getCurrentSchoolContext();
            $this->schoolId = $school?->id;
            $this->showSchoolSelector = false;
            $this->lockedSchoolName = $school
                ? ($school->suburb ? $school->name . ' (' . $school->suburb . ')' : $school->name)
                : '';
        } elseif ($user->isSchoolLevel()) {
            $school = $user->getSchool();
            $this->schoolId = $school?->id;
            $this->showSchoolSelector = false;
            $this->lockedSchoolName = $school
                ? ($school->suburb ? $school->name . ' (' . $school->suburb . ')' : $school->name)
                : '';
        } else {
            $this->showSchoolSelector = true;
        }
    }

    public function downloadSampleCsv()
    {
        $filename = 'import-users-sample.csv';

        // Only show example rows for roles the logged-in user is actually
        // allowed to assign (same restriction as the role dropdown/validation).
        $allowedLabels = $this->roleOptions;
        $sampleRows = [
            RoleHelper::ROLE_SCHOOL_ADMIN => ['Jane', 'Smith', 'jane.smith@gmail.com', RoleHelper::ROLE_SCHOOL_ADMIN],
            RoleHelper::ROLE_PHOTO_COORDINATOR => ['John', 'Doe', 'john.doe@gmail.com', RoleHelper::ROLE_PHOTO_COORDINATOR],
            RoleHelper::ROLE_TEACHER => ['Amy', 'Lee', 'amy.lee@gmail.com', RoleHelper::ROLE_TEACHER],
        ];

        return response()->streamDownload(function () use ($allowedLabels, $sampleRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['firstname', 'lastname', 'email address', 'user role']);
            foreach ($sampleRows as $role => $exampleRow) {
                if (array_key_exists($role, $allowedLabels)) {
                    fputcsv($handle, $exampleRow);
                }
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /** Choosing or dropping a file imports it straight away. */
    public function updatedFile(): void
    {
        $this->resetErrorBag('file');

        if ($this->file) {
            $this->uploadFile();
        }
    }

    public function uploadFile(): void
    {
        if (!$this->file) {
            $this->addError('file', 'Please choose a CSV or Excel file.');

            return;
        }

        $this->successMessage = '';
        $this->topErrors = [];
        $this->rows = [];
        $this->editingRows = [];
        $this->resetPage();

        try {
            $this->validate([
                'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            ], [
                'file.required' => 'Please choose a CSV or Excel file.',
                'file.mimes' => 'File must be CSV or Excel (.xlsx, .xls, .csv, .txt).',
            ]);

            $path = $this->file->getRealPath();
            if (!$path || !is_readable($path)) {
                throw new \RuntimeException('Could not read the uploaded file. Please try again.');
            }

            $parsed = $this->parseSpreadsheet($path);
            $this->rows = $this->validateRows($parsed);
            $this->refreshTopErrors();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('Import users parse failed', ['exception' => $e->getMessage()]);
            $this->topErrors = [$e->getMessage()];
            $this->rows = [];
        } finally {
            // We've already read everything we need from the upload - remove
            // it from image_repository/livewire-tmp right away instead of
            // leaving it for Livewire's own (much later) temp-file cleanup.
            try {
                $this->file?->delete();
            } catch (\Throwable $e) {
                Log::warning('Failed to delete temporary upload', ['exception' => $e->getMessage()]);
            }
            $this->reset('file');
        }
    }

    public function updated($name, $value): void
    {
        if (!preg_match('/^rows\.(\d+)\.(firstname|lastname|email|role|send_invitation_with_proofing)$/', (string) $name, $matches)) {
            return;
        }

        $index = (int) $matches[1];
        $field = $matches[2];
        if (!isset($this->rows[$index])) {
            return;
        }

        if ($field === 'email') {
            $this->rows[$index]['email'] = strtolower(trim((string) ($this->rows[$index]['email'] ?? '')));
        } elseif ($field === 'send_invitation_with_proofing') {
            $this->rows[$index]['send_invitation_with_proofing'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } else {
            $this->rows[$index][$field] = trim((string) ($this->rows[$index][$field] ?? ''));
        }

        if (($this->rows[$index]['role'] ?? '') === RoleHelper::ROLE_SCHOOL_ADMIN) {
            $this->rows[$index]['send_invitation_with_proofing'] = false;
        }

        $this->revalidateAllRows();
        $this->successMessage = '';
    }

    public function toggleSendInvitationWithProofing(int $index): void
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        if (($this->rows[$index]['role'] ?? '') === RoleHelper::ROLE_SCHOOL_ADMIN) {
            $this->rows[$index]['send_invitation_with_proofing'] = false;
            return;
        }

        $current = !empty($this->rows[$index]['send_invitation_with_proofing']);
        $this->rows[$index]['send_invitation_with_proofing'] = !$current;
        $this->revalidateAllRows();
        $this->successMessage = '';
    }

    public function setSendInvitationWithProofing(int $index, bool $value): void
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        if (($this->rows[$index]['role'] ?? '') === RoleHelper::ROLE_SCHOOL_ADMIN) {
            $this->rows[$index]['send_invitation_with_proofing'] = false;
            return;
        }

        $this->rows[$index]['send_invitation_with_proofing'] = $value;
        $this->revalidateAllRows();
        $this->successMessage = '';
    }

    /**
     * Header "select all" checkbox for the Send Invitation with proofing
     * column. Sets every row to $value, then leans on revalidateAllRows()
     * (same as setSendInvitationWithProofing()) to clamp School
     * Administrator rows back to false, since they never show a checkbox.
     */
    public function toggleAllSendInvitationWithProofing(bool $value): void
    {
        foreach ($this->rows as $index => $row) {
            $this->rows[$index]['send_invitation_with_proofing'] = $value;
        }

        $this->revalidateAllRows();
        $this->successMessage = '';
    }

    public function startEdit(int $index): void
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        $this->editingRows[$index] = true;
    }

    public function finishEdit(int $index): void
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        $this->rows[$index]['firstname'] = trim((string) ($this->rows[$index]['firstname'] ?? ''));
        $this->rows[$index]['lastname'] = trim((string) ($this->rows[$index]['lastname'] ?? ''));
        $this->rows[$index]['email'] = strtolower(trim((string) ($this->rows[$index]['email'] ?? '')));
        $this->rows[$index]['role'] = trim((string) ($this->rows[$index]['role'] ?? ''));
        $this->rows[$index]['send_invitation_with_proofing'] = !empty($this->rows[$index]['send_invitation_with_proofing']);

        if ($this->rows[$index]['role'] === RoleHelper::ROLE_SCHOOL_ADMIN) {
            $this->rows[$index]['send_invitation_with_proofing'] = false;
        }

        unset($this->editingRows[$index]);
        $this->revalidateAllRows();
        $this->successMessage = '';
    }

    public function removeRow(int $index): void
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        unset($this->rows[$index]);
        unset($this->editingRows[$index]);
        $this->rows = array_values($this->rows);
        $this->editingRows = [];
        $this->revalidateAllRows();
    }

    private function revalidateAllRows(): void
    {
        $this->rows = $this->validateRows(array_map(fn ($row) => [
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'email' => $row['email'],
            'role' => $row['role'],
            'send_invitation_with_proofing' => !empty($row['send_invitation_with_proofing']) && ($row['role'] !== RoleHelper::ROLE_SCHOOL_ADMIN),
        ], $this->rows));
        $this->refreshTopErrors();
    }

    public function submit(): void
    {
        $this->successMessage = '';
        $this->rows = $this->validateRows(array_map(fn ($row) => [
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'email' => $row['email'],
            'role' => $row['role'],
            'send_invitation_with_proofing' => !empty($row['send_invitation_with_proofing']) && ($row['role'] !== RoleHelper::ROLE_SCHOOL_ADMIN),
        ], $this->rows));
        $this->refreshTopErrors();

        if (empty($this->rows)) {
            $this->topErrors = ['Import a spreadsheet before sending invitations.'];

            return;
        }

        if (!empty($this->topErrors) || $this->hasRowErrors()) {
            return;
        }

        if (!$this->showSchoolSelector) {
            if (SchoolContextHelper::isSchoolContext()) {
                $this->schoolId = SchoolContextHelper::getCurrentSchoolContext()?->id;
            } elseif (Auth::user()->isSchoolLevel()) {
                $this->schoolId = Auth::user()->getSchool()?->id;
            }
        }

        if (empty($this->schoolId)) {
            $this->topErrors = ['Please select a school.'];

            return;
        }

        $creator = Auth::user();
        $school = School::find($this->schoolId);
        if (!$school || !$this->creatorCanAssignSchool($creator, (int) $this->schoolId)) {
            $this->topErrors = ['You are not allowed to assign this school.'];

            return;
        }

        $created = 0;
        $failed = [];

        foreach ($this->rows as $index => $row) {
            $roleName = self::ROLE_LABEL_MAP[$row['role']] ?? null;
            $role = $roleName ? Role::where('name', $roleName)->first() : null;
            if (!$role) {
                $failed[] = ($row['email'] ?: 'no email') . ': role could not be resolved.';
                continue;
            }

            $sendWithProofing = !empty($row['send_invitation_with_proofing']) && ($role->name !== RoleHelper::ROLE_SCHOOL_ADMIN);

            try {
                DB::beginTransaction();

                $statusName = $sendWithProofing ? 'invited' : 'new';
                $status = Status::where('status_external_name', $statusName)->first()
                    ?? Status::where('status_external_name', 'new')->first();

                if (!$status) {
                    throw new \RuntimeException('User status not found.');
                }

                $user = User::create([
                    'name' => $row['firstname'] . ' ' . $row['lastname'],
                    'email' => $row['email'],
                    'username' => $row['email'],
                    'firstname' => $row['firstname'],
                    'lastname' => $row['lastname'],
                    'status' => $sendWithProofing ? User::STATUS_INVITED : User::STATUS_NEW,
                    'password' => Hash::make(Str::random(32)),
                    'active_status_id' => $status->id,
                    'email_verified_at' => now(),
                    'send_invitation_with_proofing' => $sendWithProofing ? 1 : 0,
                ]);

                $user->assignRole($role->name);

                SchoolUser::create([
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                ]);

                ActivityLogHelper::log(LogConstants::CREATE_USER, ['created_user' => $user->id]);

                DB::commit();

                if ($sendWithProofing) {
                    // Suppress immediate account setup email. Email will be sent when assigned to a folder in proofing.
                } else {
                    $this->userService->sendInvite($user, $creator->id);
                }

                $created++;
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Import users create failed', [
                    'email' => $row['email'],
                    'exception' => $e->getMessage(),
                ]);
                $failed[] = "Unable to create user for email address '{$row['email']}'.";
            }
        }

        if ($created > 0) {
            $this->rows = [];
            $this->editingRows = [];
            $this->topErrors = $failed;
            $this->successMessage = $created === 1
                ? '1 user was created successfully.'
                : "{$created} users were created successfully.";
            $this->js('window.scrollTo({ top: 0, behavior: "smooth" })');
        } else {
            $this->topErrors = $failed ?: ['No users were created.'];
        }
    }

    public function getRoleOptionsProperty(): array
    {
        $creator = Auth::user();
        $allowedDbNames = RoleHelper::getAllowedRoleNames($creator->getRole());

        $options = [];
        foreach (self::ROLE_LABEL_MAP as $label => $dbName) {
            if (in_array($dbName, $allowedDbNames, true)) {
                $options[$label] = $label;
            }
        }

        return $options;
    }

    public function render()
    {
        $total = count($this->rows);
        $lastPage = max(1, (int) ceil($total / self::ROWS_PER_PAGE));
        if ($this->getPage() > $lastPage) {
            $this->setPage($lastPage);
        }
        $page = $this->getPage();

        // Keys are preserved so the view binds each row to its real index in $rows.
        $pagedRows = new LengthAwarePaginator(
            array_slice($this->rows, ($page - 1) * self::ROWS_PER_PAGE, self::ROWS_PER_PAGE, true),
            $total,
            self::ROWS_PER_PAGE,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return view('livewire.bulk-invite-users', [
            'pagedRows' => $pagedRows,
            'roleOptions' => $this->roleOptions,
            'validRoleLabels' => array_keys(self::ROLE_LABEL_MAP),
        ]);
    }

    /**
     * Parse spreadsheet by column order: A=firstname, B=lastname, C=email, D=role.
     * A header row is optional; if present it is skipped.
     *
     * @return array<int, array{firstname: string, lastname: string, email: string, role: string}>
     */
    private function parseSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $raw = $sheet->toArray(null, true, true, false);

        if (empty($raw)) {
            throw new \RuntimeException('The file is empty.');
        }

        // Optional header row — skip when the first row looks like labels.
        $first = array_map(fn ($value) => strtolower(trim((string) $value)), $raw[0] ?? []);
        if (
            ($first[0] ?? '') === 'firstname'
            || ($first[1] ?? '') === 'lastname'
            || ($first[2] ?? '') === 'email'
            || ($first[3] ?? '') === 'role'
        ) {
            array_shift($raw);
        }

        $parsed = [];
        foreach ($raw as $row) {
            if ($this->isEmptySpreadsheetRow($row)) {
                continue;
            }

            $parsed[] = [
                'firstname' => trim((string) ($row[0] ?? '')),
                'lastname' => trim((string) ($row[1] ?? '')),
                'email' => strtolower(trim((string) ($row[2] ?? ''))),
                'role' => trim((string) ($row[3] ?? '')),
                'send_invitation_with_proofing' => false,
            ];
        }

        if (empty($parsed)) {
            throw new \RuntimeException('Imported file is empty.');
        }

        return $parsed;
    }

    private function isEmptySpreadsheetRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, array{firstname: string, lastname: string, email: string, role: string}>  $parsed
     * @return array<int, array{firstname: string, lastname: string, email: string, role: string, errors: array<int, string>}>
     */
    private function validateRows(array $parsed): array
    {
        $rows = [];
        foreach ($parsed as $index => $row) {
            $rows[$index] = array_merge($row, [
                'errors' => $this->rowErrors($row, $index, $parsed),
            ]);
        }

        return $rows;
    }

    /**
     * @param  array{firstname: string, lastname: string, email: string, role: string}  $row
     * @param  array<int, array{firstname: string, lastname: string, email: string, role: string}>|null  $all
     * Errors keyed by kind: missing, invalid_email, exists, duplicate, role.
     * The top error box groups rows by kind (see refreshTopErrors()).
     *
     * @return array<string, string>
     */
    private function rowErrors(array $row, int $index, ?array $all = null): array
    {
        $errors = [];
        // Messages name the row by its email, so they can be told apart in the top error list.
        $for = $row['email'] !== '' ? " for email address '{$row['email']}'" : '';
        $all = $all ?? array_map(fn ($r) => [
            'firstname' => $r['firstname'],
            'lastname' => $r['lastname'],
            'email' => $r['email'],
            'role' => $r['role'],
        ], $this->rows);

        if ($row['firstname'] === '') {
            $errors['missing'] = "First name is required{$for}.";
        }
        if ($row['lastname'] === '') {
            $errors['missing'] = "Last name is required{$for}.";
        }
        if ($row['email'] === '') {
            $errors['missing'] = 'Email address is required.';
        } elseif (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['invalid_email'] = "Invalid email address '{$row['email']}'.";
        } elseif (User::where('email', $row['email'])->exists()) {
            $errors['exists'] = "Email address '{$row['email']}' already exists. Please contact the Franchise.";
        } else {
            foreach ($all as $i => $other) {
                if ($i !== $index && strcasecmp($other['email'] ?? '', $row['email']) === 0) {
                    $errors['duplicate'] = "Duplicate email address '{$row['email']}' in spreadsheet.";
                    break;
                }
            }
        }

        // No role, an unknown role label, or a role this user may not assign.
        $role = $row['role'] ?? '';
        if ($role === '') {
            $errors['role'] = "Role is required{$for}.";
        } elseif (!array_key_exists($role, self::ROLE_LABEL_MAP)) {
            $errors['role'] = "Invalid role \"{$role}\"{$for}.";
        } else {
            $dbName = self::ROLE_LABEL_MAP[$role];
            $allowedDbNames = RoleHelper::getAllowedRoleNames(Auth::user()->getRole());
            if (!in_array($dbName, $allowedDbNames, true)) {
                $errors['role'] = "You are not allowed to assign the role \"{$role}\"{$for}.";
            }
        }

        return $errors;
    }

    /**
     * One message per kind of error, naming every email that has it. Rows
     * missing a first name, last name or email get the generic message.
     */
    private function refreshTopErrors(): void
    {
        $emailsByKind = [];
        $hasMissing = false;

        foreach ($this->rows as $row) {
            foreach (array_keys($row['errors'] ?? []) as $kind) {
                if ($kind === 'missing') {
                    $hasMissing = true;
                    continue;
                }
                $email = (string) ($row['email'] ?? '');
                $emailsByKind[$kind][$email] = $email;
            }
        }

        // 'a@x.com', 'b@y.com' - every email with that error
        $list = fn (string $kind, string $quote) => implode(', ', array_map(
            fn ($email) => $quote . $email . $quote,
            array_values(array_filter($emailsByKind[$kind] ?? []))
        ));

        $messages = [];

        if (!empty($emailsByKind['role'])) {
            $emails = $list('role', "'");
            $messages[] = "Invalid role selected for {$emails}. Please change the role.";
        }
        if (!empty($emailsByKind['exists'])) {
            $emails = $list('exists', "'");
            $messages[] = "User {$emails} already exists. Please click 'Remove User'.";
        }
        if (!empty($emailsByKind['duplicate'])) {
            $emails = $list('duplicate', "'");
            $messages[] = "User {$emails} is duplicated. Please click 'Remove User'.";
        }
        if (!empty($emailsByKind['invalid_email'])) {
            $emails = $list('invalid_email', "'");
            $messages[] = "User {$emails} is invalid. Please click 'Edit User' and update the email address.";
        }
        if ($hasMissing) {
            $messages[] = 'Spreadsheet is missing information. Please review the highlighted rows and correct them before inviting.';
        }

        $this->topErrors = $messages;
    }

    private function hasRowErrors(): bool
    {
        foreach ($this->rows as $row) {
            if (!empty($row['errors'])) {
                return true;
            }
        }

        return false;
    }

    private function creatorCanAssignSchool(User $creator, int $schoolId): bool
    {
        if ($creator->isAdmin()) {
            return true;
        }

        if ($creator->isFranchiseLevel()) {
            return School::where('id', $schoolId)
                ->whereHas('franchises', fn ($q) => $q->where('franchise_id', $creator->getFranchise()->id))
                ->exists();
        }

        if ($creator->isSchoolLevel()) {
            return (int) $creator->getSchool()?->id === $schoolId;
        }

        return false;
    }
}
