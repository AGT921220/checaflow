<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Attendance;

use App\Features\Attendance\Application\CalculateDailyAttendance;
use App\Features\Attendance\Application\GetTodayEmployeeStatus;
use App\Features\Attendance\Application\LoadEmployeeShift;
use App\Features\Attendance\Application\RegisterPunch;
use App\Features\Attendance\Domain\AttendanceLog;
use App\Features\Attendance\Domain\AttendanceLogRepository;
use App\Features\Attendance\Domain\AttendanceStatus;
use App\Features\Attendance\Domain\CompanyClock;
use App\Features\Attendance\Domain\CompanyDailyReportCriteria;
use App\Features\Attendance\Domain\DailyAttendance;
use App\Features\Attendance\Domain\DailyAttendanceRepository;
use App\Features\Attendance\Domain\Exceptions\DuplicatePunch;
use App\Features\Attendance\Domain\Exceptions\InvalidPunchSequence;
use App\Features\Attendance\Domain\Exceptions\PunchOutsideGeofence;
use App\Features\Attendance\Domain\PunchSource;
use App\Features\Attendance\Domain\PunchType;
use App\Features\Attendance\Domain\RegisterPunchData;
use App\Features\Attendance\Domain\WorkDate;
use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\Exceptions\EmployeeNotFound;
use App\Features\Employees\Domain\SearchEmployeesCriteria;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\GeoPoint;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UserRole;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class RegisterPunchTest extends TestCase
{
    public function test_a_mobile_punch_uses_server_time_and_stores_the_daily_summary(): void
    {
        $world = $this->world('2026-11-20 09:11:00');
        $action = $this->register($world);

        $log = $action->handle(new RegisterPunchData(
            new CompanyId(4),
            10,
            PunchType::ClockIn,
            PunchSource::MobileApp,
            UserRole::Employee,
            new CompanyId(4),
            punchedAt: new DateTimeImmutable('2026-11-19 08:00:00', new DateTimeZone('UTC')),
        ));

        $this->assertSame('2026-11-20 09:11:00', $log->punchedAt()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
        $this->assertSame(AttendanceStatus::Late, $world->summaries->saved[0]->status());
        $this->assertSame(1, $world->summaries->saved[0]->lateMinutes());
    }

    public function test_a_punch_for_another_company_is_hidden(): void
    {
        $world = $this->world('2026-11-20 09:00:00');

        $this->expectException(EmployeeNotFound::class);

        $this->register($world)->handle(new RegisterPunchData(
            new CompanyId(9),
            10,
            PunchType::ClockIn,
            PunchSource::MobileWeb,
            UserRole::Employee,
            new CompanyId(9),
        ));
    }

    public function test_skipping_a_punch_does_not_store_a_log(): void
    {
        $world = $this->world('2026-11-20 14:00:00');

        try {
            $this->register($world)->handle(new RegisterPunchData(
                new CompanyId(4),
                10,
                PunchType::LunchOut,
                PunchSource::MobileWeb,
                UserRole::Employee,
                new CompanyId(4),
            ));
            $this->fail('Expected an invalid sequence.');
        } catch (InvalidPunchSequence) {
            $this->assertSame([], $world->logs->saved);
        }
    }

    public function test_a_duplicate_punch_is_rejected(): void
    {
        $world = $this->world('2026-11-20 09:00:00');
        $this->register($world)->handle(new RegisterPunchData(
            new CompanyId(4),
            10,
            PunchType::ClockIn,
            PunchSource::MobileWeb,
            UserRole::Employee,
            new CompanyId(4),
        ));

        $this->expectException(DuplicatePunch::class);

        $this->register($world)->handle(new RegisterPunchData(
            new CompanyId(4),
            10,
            PunchType::ClockIn,
            PunchSource::MobileWeb,
            UserRole::Employee,
            new CompanyId(4),
        ));
    }

    public function test_a_required_geofence_rejects_a_far_punch(): void
    {
        $world = $this->world('2026-11-20 09:00:00', requireGeofence: true);

        $this->expectException(PunchOutsideGeofence::class);

        $this->register($world)->handle(new RegisterPunchData(
            new CompanyId(4),
            10,
            PunchType::ClockIn,
            PunchSource::MobileApp,
            UserRole::Employee,
            new CompanyId(4),
            location: new GeoPoint(19.50, -99.133209),
        ));
    }

    public function test_today_status_exposes_the_next_punch(): void
    {
        $world = $this->world('2026-11-20 09:00:00');
        $this->register($world)->handle(new RegisterPunchData(
            new CompanyId(4),
            10,
            PunchType::ClockIn,
            PunchSource::MobileApp,
            UserRole::Employee,
            new CompanyId(4),
        ));

        $status = (new GetTodayEmployeeStatus(
            new LoadEmployeeShift($world->employees, $world->branches, $world->schedules, $world->clock),
            $world->clock,
            $world->logs,
        ))->handle(new CompanyId(4), 10);

        $this->assertSame(PunchType::LunchOut, $status->nextPunchType);
        $this->assertCount(1, $status->punches);
        $this->assertSame(AttendanceStatus::Incomplete, $status->status);
    }

    private function register(AttendanceWorld $world): RegisterPunch
    {
        $shift = new LoadEmployeeShift($world->employees, $world->branches, $world->schedules, $world->clock);

        return new RegisterPunch(
            $shift,
            $world->clock,
            $world->logs,
            new CalculateDailyAttendance($shift, $world->logs, $world->summaries),
            new AuthorizeActor(new RolePermissions),
        );
    }

    private function world(string $now, bool $requireGeofence = false): AttendanceWorld
    {
        $companyId = new CompanyId(4);
        $center = new GeoPoint(19.432608, -99.133209);
        $branch = Branch::open(
            $companyId,
            'Plant',
            null,
            $requireGeofence ? $center : null,
            $requireGeofence ? 150 : null,
            $requireGeofence,
        )->withId(3);
        $schedule = Schedule::open($companyId, 'Office', '09:00', '18:00', 10, 60, ['friday'])->withId(8);
        $employee = Employee::hire($companyId, 7, 3, 8, 'E-10', null, null)->withId(10);

        return new AttendanceWorld(
            new PunchEmployees($employee),
            new PunchBranches($branch),
            new PunchSchedules($schedule),
            new PunchLogs,
            new PunchSummaries,
            new FrozenClock(new DateTimeImmutable($now, new DateTimeZone('UTC'))),
        );
    }
}

final class AttendanceWorld
{
    public function __construct(
        public PunchEmployees $employees,
        public PunchBranches $branches,
        public PunchSchedules $schedules,
        public PunchLogs $logs,
        public PunchSummaries $summaries,
        public FrozenClock $clock,
    ) {}
}

final class FrozenClock implements CompanyClock
{
    public function __construct(private DateTimeImmutable $now) {}

    public function timezone(CompanyId $companyId): ?string
    {
        if ($companyId->value === 9) {
            return null;
        }

        return 'UTC';
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}

final class PunchEmployees implements EmployeeRepository
{
    public function __construct(private Employee $employee) {}

    public function save(Employee $employee): Employee
    {
        return $employee;
    }

    public function find(CompanyId $companyId, int $employeeProfileId): ?Employee
    {
        if (! $this->employee->belongsTo($companyId) || $this->employee->id() !== $employeeProfileId) {
            return null;
        }

        return $this->employee;
    }

    public function search(SearchEmployeesCriteria $criteria): array
    {
        return [];
    }

    public function count(CompanyId $companyId): int
    {
        return 0;
    }

    public function codeExists(CompanyId $companyId, string $employeeCode): bool
    {
        return false;
    }

    public function userAlreadyHasProfile(int $userId): bool
    {
        return false;
    }
}

final class PunchBranches implements BranchRepository
{
    public function __construct(private Branch $branch) {}

    public function save(Branch $branch): Branch
    {
        return $branch;
    }

    public function find(CompanyId $companyId, int $branchId): ?Branch
    {
        if (! $this->branch->belongsTo($companyId) || $this->branch->id() !== $branchId) {
            return null;
        }

        return $this->branch;
    }

    public function countActiveByCompanyId(CompanyId $companyId): int
    {
        return 0;
    }

    public function search(SearchBranchesCriteria $criteria): array
    {
        return [];
    }
}

final class PunchSchedules implements ScheduleRepository
{
    public function __construct(private Schedule $schedule) {}

    public function save(Schedule $schedule): Schedule
    {
        return $schedule;
    }

    public function find(CompanyId $companyId, int $scheduleId): ?Schedule
    {
        if (! $this->schedule->belongsTo($companyId) || $this->schedule->id() !== $scheduleId) {
            return null;
        }

        return $this->schedule;
    }

    public function search(SearchSchedulesCriteria $criteria): array
    {
        return [];
    }
}

final class PunchLogs implements AttendanceLogRepository
{
    /** @var list<AttendanceLog> */
    public array $saved = [];

    public function save(AttendanceLog $log): AttendanceLog
    {
        $stored = $log->withId(count($this->saved) + 1);
        $this->saved[] = $stored;

        return $stored;
    }

    public function forEmployeeOnDate(CompanyId $companyId, int $employeeProfileId, WorkDate $workDate): array
    {
        return array_values(array_filter(
            $this->saved,
            fn (AttendanceLog $log): bool => $log->companyId()->equals($companyId)
                && $log->employeeProfileId() === $employeeProfileId
                && $log->workDate()->equals($workDate),
        ));
    }
}

final class PunchSummaries implements DailyAttendanceRepository
{
    /** @var list<DailyAttendance> */
    public array $saved = [];

    public function save(DailyAttendance $summary): DailyAttendance
    {
        $stored = $summary->id() === null ? $summary->withId(count($this->saved) + 1) : $summary;
        $this->saved[] = $stored;

        return $stored;
    }

    public function find(CompanyId $companyId, int $employeeProfileId, WorkDate $workDate): ?DailyAttendance
    {
        foreach ($this->saved as $summary) {
            if ($summary->companyId()->equals($companyId)
                && $summary->employeeProfileId() === $employeeProfileId
                && $summary->workDate()->equals($workDate)) {
                return $summary;
            }
        }

        return null;
    }

    public function search(CompanyDailyReportCriteria $criteria): array
    {
        return [];
    }

    public function searchRange(\App\Features\Attendance\Domain\CompanyAttendanceRangeCriteria $criteria): array
    {
        return [];
    }
}
