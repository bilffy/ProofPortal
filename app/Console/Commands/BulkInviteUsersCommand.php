<?php

namespace App\Console\Commands;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Helpers\RoleHelper;
use App\Jobs\SendUserInviteJob;
use App\Models\Franchise;
use App\Models\FranchiseUser;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Status;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class BulkInviteUsersCommand extends Command
{
    protected $signature = 'users:bulk-invite
                            {file : CSV file name (in public/imports) or absolute path}
                            {--sync : Send invitation emails immediately instead of queueing}
                            {--dry-run : Validate the CSV without creating users or sending invites}';
    // # Validate only
    // php artisan users:bulk-invite my-users.csv --dry-run

    // # Queue invites (requires queue worker)
    // php artisan users:bulk-invite my-users.csv

    // # Send invites immediately (no queue)
    // php artisan users:bulk-invite my-users.csv --sync
    protected $description = 'Bulk create users from CSV and send invitation emails';

    private const REQUIRED_HEADERS = [
        'firstname',
        'lastname',
        'email',
        'franchise_alphacode',
        'school_id',
        'role_id',
        'sender_id',
    ];

    private const SCHOOL_ROLES = [
        RoleHelper::ROLE_SCHOOL_ADMIN,
        RoleHelper::ROLE_PHOTO_COORDINATOR,
        RoleHelper::ROLE_TEACHER,
    ];

    public function __construct(private UserService $userService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->resolveCsvPath($this->argument('file'));
        if ($path === null) {
            $this->error('CSV file not found.');

            return self::FAILURE;
        }

        $rows = $this->readCsv($path);
        if ($rows === null) {
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run mode — no users will be created and no invites will be sent.');
        }

        $created = 0;
        $invited = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $result = $this->processRow($row, $line);

            match ($result) {
                'created' => $created++,
                'invited' => $invited++,
                'skipped' => $skipped++,
                default => $failed++,
            };
        }

        $this->newLine();
        $this->info("Finished: {$created} created, {$invited} invited, {$skipped} skipped, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveCsvPath(string $file): ?string
    {
        $candidates = [
            $file,
            public_path('imports/' . $file),
            base_path($file),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function readCsv(string $path): ?array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error("Unable to open CSV: {$path}");

            return null;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->error('CSV file is empty.');

            return null;
        }

        $header = array_map(fn ($value) => strtolower(trim((string) $value)), $header);
        $missing = array_diff(self::REQUIRED_HEADERS, $header);
        if (!empty($missing)) {
            fclose($handle);
            $this->error('CSV is missing required columns: ' . implode(', ', $missing));

            return null;
        }

        $rows = [];
        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if ($this->isEmptyRow($data)) {
                continue;
            }

            if (count($data) !== count($header)) {
                $this->warn("Line {$line}: column count mismatch, skipping.");
                continue;
            }

            $rows[] = array_combine($header, array_map('trim', $data));
        }

        fclose($handle);

        if (empty($rows)) {
            $this->error('No data rows found in CSV.');

            return null;
        }

        $this->info('Loaded ' . count($rows) . ' row(s) from ' . $path);

        return $rows;
    }

    private function isEmptyRow(array $data): bool
    {
        return count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0;
    }

    private function processRow(array $row, int $line): string
    {
        $row['school_id'] = $row['school_id'] === '' ? null : $row['school_id'];

        $validator = Validator::make($row, [
            'firstname' => 'required|string|max:50',
            'lastname' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'franchise_alphacode' => 'required|string|max:50',
            'school_id' => 'nullable|integer',
            'role_id' => 'required|integer',
            'sender_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $this->error("Line {$line} ({$row['email']}): " . $validator->errors()->first());

            return 'failed';
        }

        $sender = User::find($row['sender_id']);
        if (!$sender) {
            $this->error("Line {$line} ({$row['email']}): sender_id {$row['sender_id']} not found.");

            return 'failed';
        }

        $role = Role::find($row['role_id']);
        if (!$role) {
            $this->error("Line {$line} ({$row['email']}): role_id {$row['role_id']} not found.");

            return 'failed';
        }

        $franchise = Franchise::where('alphacode', $row['franchise_alphacode'])->first();
        if (!$franchise) {
            $this->error("Line {$line} ({$row['email']}): franchise alphacode '{$row['franchise_alphacode']}' not found.");

            return 'failed';
        }

        $isSchoolRole = in_array($role->name, self::SCHOOL_ROLES, true);
        $school = null;

        if ($isSchoolRole) {
            if (empty($row['school_id'])) {
                $this->error("Line {$line} ({$row['email']}): school_id is required for {$role->name}.");

                return 'failed';
            }

            $school = School::find($row['school_id']);
            if (!$school) {
                $this->error("Line {$line} ({$row['email']}): school_id {$row['school_id']} not found.");

                return 'failed';
            }

            if (!$school->franchises()->where('franchise_id', $franchise->id)->exists()) {
                $this->error("Line {$line} ({$row['email']}): school {$school->name} is not linked to franchise {$franchise->alphacode}.");

                return 'failed';
            }
        } elseif (!empty($row['school_id'])) {
            $this->error("Line {$line} ({$row['email']}): school_id should be empty for {$role->name}.");

            return 'failed';
        }

        if ($this->option('dry-run')) {
            $target = $isSchoolRole
                ? "{$school->name} (school_id {$school->id})"
                : $franchise->name;
            $this->line("Line {$line}: OK — would create/invite {$row['email']} as {$role->name} for {$target}.");

            return 'invited';
        }

        $existingUser = User::where('email', $row['email'])->first();
        if ($existingUser) {
            return $this->handleExistingUser($existingUser, $role, $franchise, $school, (int) $row['sender_id'], $line);
        }

        return $this->createAndInviteUser($row, $role, $franchise, $school, (int) $row['sender_id'], $line);
    }

    private function handleExistingUser(
        User $user,
        Role $role,
        Franchise $franchise,
        ?School $school,
        int $senderId,
        int $line
    ): string {
        if (!$user->hasRole($role->name)) {
            $user->syncRoles([$role->name]);
        }

        if ($role->name === RoleHelper::ROLE_FRANCHISE) {
            FranchiseUser::firstOrCreate([
                'user_id' => $user->id,
                'franchise_id' => $franchise->id,
            ]);
        }

        if ($school !== null) {
            SchoolUser::firstOrCreate([
                'user_id' => $user->id,
                'school_id' => $school->id,
            ]);
        }

        $this->sendInvite($user, $senderId);
        $this->warn("Line {$line}: user {$user->email} already exists — role/organisation updated and invite sent.");

        return 'invited';
    }

    private function createAndInviteUser(
        array $row,
        Role $role,
        Franchise $franchise,
        ?School $school,
        int $senderId,
        int $line
    ): string {
        try {
            DB::beginTransaction();

            $status = Status::where('status_external_name', 'new')->first();
            if (!$status) {
                throw new \RuntimeException('User status "new" not found.');
            }

            $user = User::create([
                'name' => $row['firstname'] . ' ' . $row['lastname'],
                'email' => $row['email'],
                'username' => $row['email'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'status' => User::STATUS_NEW,
                'password' => Hash::make(str()->random(16)),
                'active_status_id' => $status->id,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($role->name);

            if ($role->name === RoleHelper::ROLE_FRANCHISE) {
                FranchiseUser::create([
                    'user_id' => $user->id,
                    'franchise_id' => $franchise->id,
                ]);
            }

            if ($school !== null) {
                SchoolUser::create([
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                ]);
            }

            ActivityLogHelper::log(LogConstants::CREATE_USER, ['created_user' => $user->id], $senderId);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Line {$line} ({$row['email']}): " . $e->getMessage());

            return 'failed';
        }

        $target = $school !== null
            ? "{$school->name} (school_id {$school->id})"
            : $franchise->alphacode;
        $this->sendInvite($user, $senderId);
        $this->info("Line {$line}: created and invited {$user->email} ({$role->name}, {$target}).");

        return 'created';
    }

    private function sendInvite(User $user, int $senderId): void
    {
        if ($this->option('sync')) {
            SendUserInviteJob::dispatchSync($user, $senderId);
            ActivityLogHelper::log(LogConstants::SEND_INVITE, ['invited_user' => $user->id], $senderId);

            return;
        }

        $this->userService->sendInvite($user, $senderId);
    }
}
