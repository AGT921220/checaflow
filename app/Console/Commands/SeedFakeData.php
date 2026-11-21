<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\User;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class SeedFakeData extends Command
{
    protected $signature = 'fake:seed
        {--companies=10 : Number of companies}
        {--employees=100 : Employees per company}
        {--branches=4 : Branches per company}
        {--schedules= : Schedules per company (random 10-1000 if omitted)}
        {--days=14 : Days of attendance history}
        {--prefix=fake : Unique prefix for slugs and nicknames}';

    protected $description = 'Create large fake tenant data for pagination and UI stress tests.';

    public function handle(): int
    {
        $companies = max(1, (int) $this->option('companies'));
        $employees = max(1, (int) $this->option('employees'));
        $branches = max(1, (int) $this->option('branches'));
        $schedules = $this->schedulesPerCompany();
        $days = max(0, (int) $this->option('days'));
        $prefix = Str::slug((string) $this->option('prefix'));

        if ($prefix === '') {
            $this->components->error('El prefijo no puede quedar vacío.');

            return self::FAILURE;
        }

        $plan = Plan::query()->where('code', 'enterprise')->first();

        if ($plan === null) {
            $this->components->error('Falta el plan enterprise. Corre las migraciones primero.');

            return self::FAILURE;
        }

        if (User::query()->where('nickname', 'superadmin')->doesntExist()) {
            $this->call('initial-setup');
        }

        $password = Hash::make('password');
        $now = now();
        $stamp = $now->format('YmdHis');

        $this->components->info(sprintf(
            'Generando %d empresas × %d empleados × %d días…',
            $companies,
            $employees,
            $days,
        ));

        $bar = $this->output->createProgressBar($companies);
        $bar->start();

        $createdCompanies = 0;
        $createdEmployees = 0;
        $createdSummaries = 0;

        for ($companyIndex = 1; $companyIndex <= $companies; $companyIndex++) {
            $slug = sprintf('%s-%s-%d', $prefix, $stamp, $companyIndex);

            if (DB::table('companies')->where('slug', $slug)->exists()) {
                $this->newLine();
                $this->components->error("Ya existe el slug {$slug}. Cambia --prefix.");

                return self::FAILURE;
            }

            $companyId = DB::table('companies')->insertGetId([
                'name' => 'Empresa '.$slug,
                'slug' => $slug,
                'tax_id' => null,
                'plan_id' => $plan->id,
                'plan_code' => 'enterprise',
                'max_employees' => max(50, $employees + 20),
                'max_branches' => null,
                'is_active' => true,
                'timezone' => 'America/Mexico_City',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $createdCompanies++;

            $branchIds = $this->insertBranches($companyId, $branches, $slug, $now);
            $scheduleIds = $this->insertSchedules($companyId, $schedules, $slug, $now);
            $this->insertStaff($companyId, $slug, $password, $now);

            $profiles = $this->insertEmployees(
                $companyId,
                $slug,
                $employees,
                $branchIds,
                $scheduleIds,
                $password,
                $now,
            );
            $createdEmployees += count($profiles);

            if ($days > 0 && $profiles !== []) {
                $createdSummaries += $this->insertAttendance($companyId, $profiles, $days, $now);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->components->info('Listo.');
        $this->line("Empresas: {$createdCompanies}");
        $this->line("Empleados: {$createdEmployees}");
        $this->line("Resúmenes de asistencia: {$createdSummaries}");
        $this->newLine();
        $this->line('Accesos de ejemplo (contraseña: password):');
        $this->line("  admin  → {$prefix}-{$stamp}-1-admin");
        $this->line("  hr     → {$prefix}-{$stamp}-1-hr");
        $this->line("  empleado → {$prefix}-{$stamp}-1-e1");

        return self::SUCCESS;
    }

    private function schedulesPerCompany(): int
    {
        $value = $this->option('schedules');

        if ($value === null || $value === '') {
            return random_int(10, 1000);
        }

        return max(1, (int) $value);
    }

    /**
     * @return list<int>
     */
    private function insertBranches(int $companyId, int $count, string $slug, mixed $now): array
    {
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $lat = 19.4000000 + ($i * 0.01);
            $lng = -99.1500000 - ($i * 0.01);
            $rows[] = [
                'company_id' => $companyId,
                'name' => "Sucursal {$i}",
                'address' => "Calle {$i}, {$slug}",
                'latitude' => $lat,
                'longitude' => $lng,
                'geofence_radius_meters' => 120,
                'require_geofence' => $i === 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('branches')->insert($rows);

        return DB::table('branches')
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function insertSchedules(int $companyId, int $count, string $slug, mixed $now): array
    {
        $templates = [
            ['name' => 'Matutino', 'in' => '08:00:00', 'out' => '17:00:00'],
            ['name' => 'Vespertino', 'in' => '12:00:00', 'out' => '21:00:00'],
            ['name' => 'Completo', 'in' => '09:00:00', 'out' => '18:00:00'],
            ['name' => 'Extendido', 'in' => '07:00:00', 'out' => '16:00:00'],
        ];
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $template = $templates[$i % count($templates)];
            $rows[] = [
                'company_id' => $companyId,
                'name' => $template['name'].' '.($i + 1).' '.$slug,
                'clock_in_time' => $template['in'],
                'clock_out_time' => $template['out'],
                'late_tolerance_minutes' => 10,
                'allowed_lunch_minutes' => 60,
                'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('schedules')->insert($rows);

        return DB::table('schedules')
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function insertStaff(int $companyId, string $slug, string $password, mixed $now): void
    {
        DB::table('users')->insert([
            [
                'company_id' => $companyId,
                'name' => 'Admin '.$slug,
                'first_name' => 'Admin',
                'last_name' => $slug,
                'nickname' => $slug.'-admin',
                'email' => $slug.'-admin@checaflow.test',
                'phone' => '5551000001',
                'password' => $password,
                'role' => 'company_admin',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_id' => $companyId,
                'name' => 'HR '.$slug,
                'first_name' => 'HR',
                'last_name' => $slug,
                'nickname' => $slug.'-hr',
                'email' => $slug.'-hr@checaflow.test',
                'phone' => '5551000002',
                'password' => $password,
                'role' => 'hr',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * @param  list<int>  $branchIds
     * @param  list<int>  $scheduleIds
     * @return list<array{id: int, branch_id: int, schedule_id: int}>
     */
    private function insertEmployees(
        int $companyId,
        string $slug,
        int $count,
        array $branchIds,
        array $scheduleIds,
        string $password,
        mixed $now,
    ): array {
        $userRows = [];
        $nicknames = [];

        for ($i = 1; $i <= $count; $i++) {
            $nickname = $slug.'-e'.$i;
            $nicknames[] = $nickname;
            $userRows[] = [
                'company_id' => $companyId,
                'name' => 'Empleado '.$i.' '.$slug,
                'first_name' => 'Empleado',
                'last_name' => (string) $i,
                'nickname' => $nickname,
                'email' => $nickname.'@checaflow.test',
                'phone' => sprintf('555%07d', $i),
                'password' => $password,
                'role' => 'employee',
                'is_active' => $i % 17 !== 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($userRows, 250) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        $users = DB::table('users')
            ->where('company_id', $companyId)
            ->where('role', 'employee')
            ->whereIn('nickname', $nicknames)
            ->orderBy('id')
            ->get(['id', 'nickname']);

        $profileRows = [];
        $index = 0;

        foreach ($users as $user) {
            $index++;
            $branchId = $branchIds[($index - 1) % count($branchIds)];
            $scheduleId = $scheduleIds[($index - 1) % count($scheduleIds)];
            $profileRows[] = [
                'company_id' => $companyId,
                'user_id' => (int) $user->id,
                'branch_id' => $branchId,
                'schedule_id' => $scheduleId,
                'employee_code' => sprintf('E-%04d', $index),
                'position_title' => $index % 3 === 0 ? 'Supervisor' : 'Operador',
                'hire_date' => $now->toDateString(),
                'is_active' => $index % 17 !== 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($profileRows, 250) as $chunk) {
            DB::table('employee_profiles')->insert($chunk);
        }

        return DB::table('employee_profiles')
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->get(['id', 'branch_id', 'schedule_id'])
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'branch_id' => (int) $row->branch_id,
                'schedule_id' => (int) $row->schedule_id,
            ])
            ->all();
    }

    /**
     * @param  list<array{id: int, branch_id: int, schedule_id: int}>  $profiles
     */
    private function insertAttendance(int $companyId, array $profiles, int $days, mixed $now): int
    {
        $today = new DateTimeImmutable('today');
        $summaryRows = [];
        $logRows = [];
        $inserted = 0;
        $statuses = ['on_time', 'late', 'incomplete', 'absent'];

        for ($dayOffset = 0; $dayOffset < $days; $dayOffset++) {
            $workDate = $today->sub(new DateInterval('P'.$dayOffset.'D'))->format('Y-m-d');

            foreach ($profiles as $offset => $profile) {
                $status = $statuses[($offset + $dayOffset) % count($statuses)];
                $clockIn = null;
                $lunchOut = null;
                $lunchIn = null;
                $clockOut = null;
                $late = 0;
                $lunch = 0;
                $worked = 0;

                if ($status !== 'absent') {
                    $clockIn = $workDate.' 09:'.sprintf('%02d', ($offset % 20)).':00';
                    $late = $status === 'late' ? 12 + ($offset % 20) : 0;
                }

                if ($status === 'on_time' || $status === 'late') {
                    $lunchOut = $workDate.' 13:00:00';
                    $lunchIn = $workDate.' 14:00:00';
                    $clockOut = $workDate.' 18:00:00';
                    $lunch = 60;
                    $worked = 480 - $late;
                }

                if ($status === 'incomplete' && $clockIn !== null) {
                    $lunchOut = $workDate.' 13:05:00';
                    $worked = 180;
                }

                $summaryRows[] = [
                    'company_id' => $companyId,
                    'employee_profile_id' => $profile['id'],
                    'branch_id' => $profile['branch_id'],
                    'schedule_id' => $profile['schedule_id'],
                    'work_date' => $workDate,
                    'clock_in_at' => $clockIn,
                    'lunch_out_at' => $lunchOut,
                    'lunch_in_at' => $lunchIn,
                    'clock_out_at' => $clockOut,
                    'late_minutes' => $late,
                    'lunch_minutes' => $lunch,
                    'excess_lunch_minutes' => 0,
                    'worked_minutes' => max(0, $worked),
                    'status' => $status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($clockIn !== null) {
                    $logRows[] = $this->logRow($companyId, $profile, $workDate, 'clock_in', $clockIn, $now);
                }

                if ($lunchOut !== null) {
                    $logRows[] = $this->logRow($companyId, $profile, $workDate, 'lunch_out', $lunchOut, $now);
                }

                if ($lunchIn !== null) {
                    $logRows[] = $this->logRow($companyId, $profile, $workDate, 'lunch_in', $lunchIn, $now);
                }

                if ($clockOut !== null) {
                    $logRows[] = $this->logRow($companyId, $profile, $workDate, 'clock_out', $clockOut, $now);
                }

                if (count($summaryRows) >= 400) {
                    DB::table('daily_attendance_summaries')->insert($summaryRows);
                    $inserted += count($summaryRows);
                    $summaryRows = [];
                }

                if (count($logRows) >= 400) {
                    DB::table('attendance_logs')->insert($logRows);
                    $logRows = [];
                }
            }
        }

        if ($summaryRows !== []) {
            DB::table('daily_attendance_summaries')->insert($summaryRows);
            $inserted += count($summaryRows);
        }

        if ($logRows !== []) {
            DB::table('attendance_logs')->insert($logRows);
        }

        return $inserted;
    }

    /**
     * @param  array{id: int, branch_id: int, schedule_id: int}  $profile
     * @return array<string, mixed>
     */
    private function logRow(
        int $companyId,
        array $profile,
        string $workDate,
        string $punchType,
        string $punchedAt,
        mixed $now,
    ): array {
        return [
            'company_id' => $companyId,
            'employee_profile_id' => $profile['id'],
            'branch_id' => $profile['branch_id'],
            'work_date' => $workDate,
            'punch_type' => $punchType,
            'punched_at' => $punchedAt,
            'latitude' => null,
            'longitude' => null,
            'is_within_geofence' => null,
            'source' => 'mobile_web',
            'ip_address' => '127.0.0.1',
            'notes' => 'fake-seed',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
