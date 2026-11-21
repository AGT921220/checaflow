<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Features\Attendance\Application\GetCompanyDailyReport;
use App\Features\Attendance\Domain\AttendanceStatus;
use App\Features\Attendance\Domain\CompanyClock;
use App\Features\Attendance\Domain\CompanyDailyReportCriteria;
use App\Features\Attendance\Domain\DailyAttendance;
use App\Features\Attendance\Domain\Exceptions\InvalidWorkDate;
use App\Features\Attendance\Domain\WorkDate;
use App\Features\Branches\Application\SearchBranches;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Employees\Application\SearchEmployees;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\SearchEmployeesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Exceptions\CompanyNotFound;
use App\Features\Users\Domain\UserRole;
use App\Http\Attendance\AttendanceLabels;
use App\Http\Auth\CurrentCompanyId;
use App\Http\Controllers\Controller;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DailyAttendanceController extends Controller
{
    public function index(
        Request $request,
        GetCompanyDailyReport $report,
        SearchBranches $searchBranches,
        SearchEmployees $searchEmployees,
        CompanyClock $clock,
        CurrentCompanyId $company,
        AttendanceLabels $labels,
    ): View {
        $companyId = $company->from($request);
        $role = $request->user()?->role;

        if (! is_string($role)) {
            abort(403);
        }

        $zone = $this->zone($clock, $companyId);
        $workDate = $this->workDate($request, $clock->now(), $zone);
        $branchId = $this->branchId($request);
        $branches = $searchBranches->handle(new SearchBranchesCriteria($companyId, true));
        $employees = $searchEmployees->handle(new SearchEmployeesCriteria(
            companyId: $companyId,
            branchId: $branchId,
            isActive: true,
        ));
        $summaries = $report->handle(new CompanyDailyReportCriteria(
            UserRole::from($role),
            $companyId,
            $companyId,
            $workDate,
            $branchId,
        ));
        $byEmployee = [];

        foreach ($summaries as $summary) {
            $byEmployee[$summary->employeeProfileId()] = $summary;
        }

        $board = $this->board($employees, $byEmployee, $this->names($companyId, $employees), $zone, $labels);

        return view('admin.attendance.index', [
            'title' => 'Asistencia del día',
            'workDate' => $workDate->value,
            'branchId' => $branchId,
            'branches' => $branches,
            'rows' => $board['rows'],
            'present' => $board['present'],
            'late' => $board['late'],
            'atLunch' => $board['atLunch'],
            'incomplete' => $board['incomplete'],
        ]);
    }

    private function workDate(Request $request, DateTimeImmutable $now, DateTimeZone $zone): WorkDate
    {
        $today = WorkDate::fromInstant($now, $zone);
        $requested = $request->query('work_date');

        if (! is_string($requested) || $requested === '') {
            return $today;
        }

        try {
            return new WorkDate($requested);
        } catch (InvalidWorkDate) {
            return $today;
        }
    }

    private function branchId(Request $request): ?int
    {
        $value = $request->query('branch_id');

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value) || (int) $value < 1) {
            abort(404);
        }

        return (int) $value;
    }

    /**
     * @param  list<Employee>  $employees
     * @return array<int, string>
     */
    private function names(CompanyId $companyId, array $employees): array
    {
        $ids = array_map(fn (Employee $employee): int => $employee->userId(), $employees);

        if ($ids === []) {
            return [];
        }

        return User::query()
            ->where('company_id', $companyId->value)
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param  list<Employee>  $employees
     * @param  array<int, DailyAttendance>  $summaries
     * @param  array<int, string>  $names
     * @return array{rows: list<array<string, mixed>>, present: int, late: int, atLunch: int, incomplete: int}
     */
    private function board(array $employees, array $summaries, array $names, DateTimeZone $zone, AttendanceLabels $labels): array
    {
        $rows = [];
        $present = 0;
        $late = 0;
        $atLunch = 0;
        $incomplete = 0;

        foreach ($employees as $employee) {
            $summary = $summaries[$employee->id()] ?? null;
            $rows[] = [
                'name' => (string) ($names[$employee->userId()] ?? $employee->employeeCode()),
                'code' => $employee->employeeCode(),
                'status' => $labels->status($summary?->status()->value ?? AttendanceStatus::Absent->value),
                'clockIn' => $this->time($summary?->clockInAt(), $zone),
                'lunchOut' => $this->time($summary?->lunchOutAt(), $zone),
                'lunchIn' => $this->time($summary?->lunchInAt(), $zone),
                'clockOut' => $this->time($summary?->clockOutAt(), $zone),
                'lateMinutes' => $summary?->lateMinutes() ?? 0,
                'workedMinutes' => $summary?->workedMinutes() ?? 0,
            ];

            if ($summary === null) {
                continue;
            }

            if ($summary->clockInAt() !== null) {
                $present++;
            }

            if ($summary->status() === AttendanceStatus::Late) {
                $late++;
            }

            if ($summary->lunchOutAt() !== null && $summary->lunchInAt() === null) {
                $atLunch++;
            }

            if ($summary->status() === AttendanceStatus::Incomplete) {
                $incomplete++;
            }
        }

        return [
            'rows' => $rows,
            'present' => $present,
            'late' => $late,
            'atLunch' => $atLunch,
            'incomplete' => $incomplete,
        ];
    }

    private function zone(CompanyClock $clock, CompanyId $companyId): DateTimeZone
    {
        $name = $clock->timezone($companyId);

        if ($name === null) {
            throw new CompanyNotFound;
        }

        try {
            return new DateTimeZone($name);
        } catch (Exception) {
            throw new CompanyNotFound;
        }
    }

    private function time(?DateTimeImmutable $instant, DateTimeZone $zone): ?string
    {
        if ($instant === null) {
            return null;
        }

        return $instant->setTimezone($zone)->format('H:i');
    }
}
