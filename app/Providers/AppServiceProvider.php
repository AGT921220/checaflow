<?php

namespace App\Providers;

use App\Features\Attendance\Domain\AttendanceLogRepository;
use App\Features\Attendance\Domain\CompanyClock;
use App\Features\Attendance\Domain\DailyAttendanceRepository;
use App\Features\Attendance\Infrastructure\EloquentAttendanceLogRepository;
use App\Features\Attendance\Infrastructure\EloquentCompanyClock;
use App\Features\Attendance\Infrastructure\EloquentDailyAttendanceRepository;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Infrastructure\EloquentBranchRepository;
use App\Features\Employees\Domain\CompanyMember;
use App\Features\Employees\Domain\CompanyStaffingLimit;
use App\Features\Employees\Domain\EmployeeAccountRegistrar;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Infrastructure\EloquentCompanyMember;
use App\Features\Employees\Infrastructure\EloquentCompanyStaffingLimit;
use App\Features\Employees\Infrastructure\EloquentEmployeeAccountRegistrar;
use App\Features\Employees\Infrastructure\EloquentEmployeeRepository;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Schedules\Infrastructure\EloquentScheduleRepository;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\PermissionChecker;
use App\Features\Users\Domain\PlanRepository;
use App\Features\Users\Domain\ProfilePhotoStore;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UserRepository;
use App\Features\Users\Infrastructure\EloquentCompanyRepository;
use App\Features\Users\Infrastructure\EloquentPlanRepository;
use App\Features\Users\Infrastructure\EloquentUserRepository;
use App\Features\Users\Infrastructure\PublicProfilePhotoStore;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BranchRepository::class, EloquentBranchRepository::class);
        $this->app->bind(ScheduleRepository::class, EloquentScheduleRepository::class);
        $this->app->bind(EmployeeRepository::class, EloquentEmployeeRepository::class);
        $this->app->bind(CompanyMember::class, EloquentCompanyMember::class);
        $this->app->bind(CompanyStaffingLimit::class, EloquentCompanyStaffingLimit::class);
        $this->app->bind(EmployeeAccountRegistrar::class, EloquentEmployeeAccountRegistrar::class);
        $this->app->bind(AttendanceLogRepository::class, EloquentAttendanceLogRepository::class);
        $this->app->bind(DailyAttendanceRepository::class, EloquentDailyAttendanceRepository::class);
        $this->app->bind(CompanyClock::class, EloquentCompanyClock::class);
        $this->app->bind(PermissionChecker::class, RolePermissions::class);
        $this->app->bind(CompanyRepository::class, EloquentCompanyRepository::class);
        $this->app->bind(PlanRepository::class, EloquentPlanRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(ProfilePhotoStore::class, PublicProfilePhotoStore::class);
    }

    public function boot(): void
    {
        //
    }
}
