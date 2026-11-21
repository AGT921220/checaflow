<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\EmployeeProfile;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/attendance')->assertRedirect('/login');
    }

    public function test_employee_punch_stays_inside_the_company(): void
    {
        $acme = $this->tenant('acme', 'E-100');
        $other = $this->tenant('other', 'E-200');

        $this->actingAs($acme['employee'])
            ->get('/attendance')
            ->assertOk()
            ->assertSee('Entrada');

        $this->actingAs($acme['employee'])
            ->post('/attendance/punches', ['punch_type' => 'clock_in'])
            ->assertRedirect(route('attendance.today'));

        $this->assertDatabaseHas('attendance_logs', [
            'company_id' => $acme['company']->id,
            'employee_profile_id' => $acme['profile']->id,
            'punch_type' => 'clock_in',
            'source' => 'mobile_web',
        ]);

        $this->assertDatabaseHas('daily_attendance_summaries', [
            'company_id' => $acme['company']->id,
            'employee_profile_id' => $acme['profile']->id,
            'status' => 'incomplete',
        ]);

        $this->actingAs($acme['employee'])
            ->get('/attendance')
            ->assertOk()
            ->assertSee('Salir a comer');

        $this->actingAs($acme['employee'])
            ->get('/admin/attendance')
            ->assertForbidden();

        $this->actingAs($acme['hr'])
            ->get('/admin/attendance')
            ->assertOk()
            ->assertSee('E-100')
            ->assertSee('Incompleto')
            ->assertDontSee('E-200');

        $this->actingAs($other['hr'])
            ->get('/admin/attendance')
            ->assertOk()
            ->assertSee('E-200')
            ->assertDontSee('E-100');
    }

    public function test_hr_opens_a_schedule_and_an_employee_and_the_admin_opens_a_branch(): void
    {
        $acme = $this->tenant('hire', 'E-100');

        $this->actingAs($acme['hr'])
            ->post('/admin/schedules', [
                'name' => 'Desk',
                'clock_in_time' => '10:00',
                'clock_out_time' => '19:00',
                'late_tolerance_minutes' => 5,
                'allowed_lunch_minutes' => 45,
                'working_days' => ['monday', 'tuesday'],
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.schedules.index'));

        $this->actingAs($acme['hr'])
            ->get('/admin/schedules')
            ->assertOk()
            ->assertSee('Lunes')
            ->assertSee('Desk');

        $this->actingAs($acme['admin'])
            ->post('/admin/branches', [
                'name' => 'North',
                'address' => 'One street',
                'require_geofence' => '0',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.branches.index'));

        $this->actingAs($acme['hr'])
            ->post('/admin/employees', [
                'name' => 'New Person',
                'nickname' => 'new-person',
                'email' => 'new@hire.test',
                'phone' => '5550001111',
                'password' => 'password1',
                'password_confirmation' => 'password1',
                'employee_code' => 'E-300',
                'position_title' => 'Clerk',
                'branch_id' => $acme['branch']->id,
                'schedule_id' => $acme['schedule']->id,
            ])
            ->assertRedirect(route('admin.employees.index'));

        $this->actingAs($acme['hr'])
            ->post('/admin/employees', [
                'name' => 'No Mail',
                'nickname' => 'no-mail',
                'password' => 'password1',
                'password_confirmation' => 'password1',
                'employee_code' => 'E-301',
                'branch_id' => $acme['branch']->id,
                'schedule_id' => $acme['schedule']->id,
            ])
            ->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('branches', [
            'company_id' => $acme['company']->id,
            'name' => 'North',
        ]);
        $this->assertDatabaseHas('users', [
            'nickname' => 'new-person',
            'email' => 'new@hire.test',
            'phone' => '5550001111',
            'company_id' => $acme['company']->id,
            'role' => 'employee',
        ]);
        $this->assertDatabaseHas('users', [
            'nickname' => 'no-mail',
            'email' => null,
            'company_id' => $acme['company']->id,
            'role' => 'employee',
        ]);
        $this->assertDatabaseHas('employee_profiles', [
            'company_id' => $acme['company']->id,
            'employee_code' => 'E-300',
        ]);

        $this->actingAs($acme['hr'])
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Reportes');

        $this->actingAs($acme['employee'])
            ->get('/admin/reports')
            ->assertForbidden();
    }

    public function test_login_sends_each_role_home(): void
    {
        $acme = $this->tenant('login', 'E-100');

        $this->post('/login', [
            'nickname' => $acme['employee']->email,
            'password' => 'password',
        ])->assertRedirect(route('attendance.today'));

        $this->post('/logout')->assertRedirect(route('login'));

        $this->post('/login', [
            'nickname' => $acme['hr']->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.attendance.index'));
    }

    /**
     * @return array{company: Company, branch: Branch, schedule: Schedule, employee: User, hr: User, admin: User, profile: EmployeeProfile}
     */
    private function tenant(string $slug, string $code): array
    {
        $plan = Plan::query()->where('code', 'enterprise')->firstOrFail();

        $company = Company::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'plan_id' => $plan->id,
            'plan_code' => $plan->code,
            'max_employees' => 50,
            'is_active' => true,
            'timezone' => 'America/Mexico_City',
        ]);

        $branch = Branch::query()->create([
            'company_id' => $company->id,
            'name' => 'Main',
            'require_geofence' => false,
            'is_active' => true,
        ]);

        $schedule = Schedule::query()->create([
            'company_id' => $company->id,
            'name' => 'Open',
            'clock_in_time' => '00:00:00',
            'clock_out_time' => '23:59:00',
            'late_tolerance_minutes' => 1440,
            'allowed_lunch_minutes' => 60,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true,
        ]);

        $hr = User::query()->create([
            'company_id' => $company->id,
            'name' => 'HR '.$slug,
            'email' => 'hr@'.$slug.'.test',
            'password' => 'password',
            'role' => 'hr',
            'is_active' => true,
        ]);

        $admin = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Admin '.$slug,
            'email' => 'admin@'.$slug.'.test',
            'password' => 'password',
            'role' => 'company_admin',
            'is_active' => true,
        ]);

        $employee = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Employee '.$slug,
            'email' => 'employee@'.$slug.'.test',
            'password' => 'password',
            'role' => 'employee',
            'is_active' => true,
        ]);

        $profile = EmployeeProfile::query()->create([
            'company_id' => $company->id,
            'user_id' => $employee->id,
            'branch_id' => $branch->id,
            'schedule_id' => $schedule->id,
            'employee_code' => $code,
            'position_title' => 'Operator',
            'is_active' => true,
        ]);

        return [
            'company' => $company,
            'branch' => $branch,
            'schedule' => $schedule,
            'employee' => $employee,
            'hr' => $hr,
            'admin' => $admin,
            'profile' => $profile,
        ];
    }
}
