<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmployeeProfile;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class SeedFakeDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_fake_companies_employees_and_attendance(): void
    {
        $exit = Artisan::call('fake:seed', [
            '--companies' => 2,
            '--employees' => 5,
            '--branches' => 2,
            '--schedules' => 2,
            '--days' => 3,
            '--prefix' => 'tst',
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame(2, Company::query()->where('slug', 'like', 'tst-%')->count());
        $this->assertSame(10, EmployeeProfile::query()->count());
        $this->assertTrue(User::query()->where('nickname', 'like', 'tst-%-admin')->exists());
        $this->assertDatabaseCount('daily_attendance_summaries', 30);
        $this->assertTrue(
            User::query()->where('role', 'employee')->where('nickname', 'like', 'tst-%-e1')->exists()
        );
    }

    public function test_omitted_schedules_uses_a_random_count_between_ten_and_one_thousand(): void
    {
        $exit = Artisan::call('fake:seed', [
            '--companies' => 1,
            '--employees' => 1,
            '--branches' => 1,
            '--days' => 0,
            '--prefix' => 'rnd',
        ]);

        $this->assertSame(0, $exit);
        $count = Schedule::query()->count();
        $this->assertGreaterThanOrEqual(10, $count);
        $this->assertLessThanOrEqual(1000, $count);
    }
}
