<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\EmployeeProfile;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        if (Company::query()->where('slug', 'acme')->exists()) {
            return;
        }

        $company = Company::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'tax_id' => null,
            'plan_code' => 'starter',
            'max_employees' => 50,
            'is_active' => true,
            'timezone' => 'America/Mexico_City',
        ]);

        $branch = Branch::query()->create([
            'company_id' => $company->id,
            'name' => 'Main plant',
            'address' => 'Demo street',
            'require_geofence' => false,
            'is_active' => true,
        ]);

        $schedule = Schedule::query()->create([
            'company_id' => $company->id,
            'name' => 'Office',
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
            'late_tolerance_minutes' => 10,
            'allowed_lunch_minutes' => 60,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true,
        ]);

        User::query()->create([
            'company_id' => $company->id,
            'name' => 'Ada Admin',
            'email' => 'admin@acme.test',
            'password' => 'password',
            'role' => 'company_admin',
            'is_active' => true,
        ]);

        User::query()->create([
            'company_id' => $company->id,
            'name' => 'Hugo HR',
            'email' => 'hr@acme.test',
            'password' => 'password',
            'role' => 'hr',
            'is_active' => true,
        ]);

        $employee = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Eva Employee',
            'email' => 'employee@acme.test',
            'password' => 'password',
            'role' => 'employee',
            'is_active' => true,
        ]);

        EmployeeProfile::query()->create([
            'company_id' => $company->id,
            'user_id' => $employee->id,
            'branch_id' => $branch->id,
            'schedule_id' => $schedule->id,
            'employee_code' => 'E-100',
            'position_title' => 'Operator',
            'hire_date' => '2026-01-06',
            'is_active' => true,
        ]);
    }
}
