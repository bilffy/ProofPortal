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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

class BulkInviteUsers extends Component
{
    use WithFileUploads;

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

    public function updatedFile(): void
    {
        if (!$this->file) {
            return;
        }

        $this->successMessage = '';
        $this->topErrors = [];
        $this->rows = [];
        $this->editingRows = [];

        try {
            $this->validate([
                'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            ], [
                'file.required' => 'Please choose a CSV or Excel file.',
                'file.mimes' => 'File must be CSV or Excel (.xlsx, .xls, .csv).',
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
                $failed[] = 'Row ' . ($index + 1) . ': role could not be resolved.';
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
                $failed[] = $row['email'] . ': unable to create user.';
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

    public function getSchoolsProperty()
    {
        $user = Auth::user();

        if ($user->isFranchiseLevel()) {
            $franchise = $user->getFranchise();
            if (!$franchise) {
                return collect();
            }

            return $franchise->schools()->orderBy('name')->get();
        }

        if ($user->isAdmin()) {
            return School::orderBy('name')->get();
        }

        $school = $user->getSchool();

        return $school ? collect([$school]) : collect();
    }

    public function render()
    {
        return view('livewire.bulk-invite-users', [
            'roleOptions' => $this->roleOptions,
            'schools' => $this->schools,
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
            throw new \RuntimeException('No data rows found in the spreadsheet.');
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
     * @return array<int, string>
     */
    private function rowErrors(array $row, int $index, ?array $all = null): array
    {
        $errors = [];
        $all = $all ?? array_map(fn ($r) => [
            'firstname' => $r['firstname'],
            'lastname' => $r['lastname'],
            'email' => $r['email'],
            'role' => $r['role'],
        ], $this->rows);

        if ($row['firstname'] === '') {
            $errors[] = 'First name is required.';
        }
        if ($row['lastname'] === '') {
            $errors[] = 'Last name is required.';
        }
        if ($row['email'] === '') {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        } elseif (User::where('email', $row['email'])->exists()) {
            $errors[] = 'User already exists.';
        } else {
            $duplicateIndexes = [];
            foreach ($all as $i => $other) {
                if ($i !== $index && strcasecmp($other['email'] ?? '', $row['email']) === 0) {
                    $duplicateIndexes[] = $i + 1;
                }
            }
            if (!empty($duplicateIndexes)) {
                $errors[] = 'Duplicate email in spreadsheet (also on row ' . implode(', ', $duplicateIndexes) . ').';
            }
        }

        $role = $row['role'] ?? '';
        if ($role === '') {
            $errors[] = 'Role is required.';
        } elseif (!array_key_exists($role, self::ROLE_LABEL_MAP)) {
            $allowed = implode(', ', array_keys(self::ROLE_LABEL_MAP));
            $errors[] = "Invalid role \"{$role}\". Role must be exactly one of: {$allowed}.";
        } else {
            $dbName = self::ROLE_LABEL_MAP[$role];
            $allowedDbNames = RoleHelper::getAllowedRoleNames(Auth::user()->getRole());
            if (!in_array($dbName, $allowedDbNames, true)) {
                $errors[] = "You are not allowed to assign the role \"{$role}\".";
            }
        }

        return $errors;
    }

    private function refreshTopErrors(): void
    {
        $messages = [];
        foreach ($this->rows as $index => $row) {
            foreach ($row['errors'] as $error) {
                $messages[] = 'Row ' . ($index + 1) . ' (' . ($row['email'] ?: 'no email') . '): ' . $error;
            }
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
