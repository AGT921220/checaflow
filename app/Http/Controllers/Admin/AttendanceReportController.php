<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Features\Attendance\Application\GetCompanyAttendanceRange;
use App\Features\Attendance\Domain\AttendanceStatus;
use App\Features\Attendance\Domain\CompanyAttendanceRangeCriteria;
use App\Features\Attendance\Domain\CompanyClock;
use App\Features\Attendance\Domain\DailyAttendance;
use App\Features\Attendance\Domain\WorkDate;
use App\Features\Branches\Application\SearchBranches;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Employees\Application\SearchEmployees;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\SearchEmployeesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Exceptions\CompanyNotFound;
use App\Features\Users\Domain\UserRole;
use App\Http\Auth\CurrentCompanyId;
use App\Http\Controllers\Controller;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AttendanceReportController extends Controller
{
    public function index(
        Request $request,
        GetCompanyAttendanceRange $report,
        SearchBranches $searchBranches,
        SearchEmployees $searchEmployees,
        CompanyClock $clock,
        CurrentCompanyId $company,
    ): View {
        $companyId = $company->from($request);
        $role = $request->user()?->role;

        if (! is_string($role)) {
            abort(403);
        }

        $zone = $this->zone($clock, $companyId);
        [$fromDate, $toDate] = $this->range($request, $zone);
        $branchId = $this->branchId($request);
        $branches = $searchBranches->handle(new SearchBranchesCriteria($companyId, true));
        $employees = $searchEmployees->handle(new SearchEmployeesCriteria(
            companyId: $companyId,
            branchId: $branchId,
            isActive: true,
        ));
        $summaries = $report->handle(new CompanyAttendanceRangeCriteria(
            UserRole::from($role),
            $companyId,
            $companyId,
            $fromDate,
            $toDate,
            $branchId,
        ));

        return view('admin.reports.index', [
            'title' => 'Reportes',
            'fromDate' => $fromDate->value,
            'toDate' => $toDate->value,
            'branchId' => $branchId,
            'branches' => $branches,
            'rows' => $this->rows($employees, $summaries, $this->names($companyId, $employees), $fromDate, $toDate),
        ]);
    }

    /**
     * @param  list<Employee>  $employees
     * @param  list<DailyAttendance>  $summaries
     * @param  array<int, string>  $names
     * @return list<array{
     *     name: string,
     *     code: string,
     *     presentDays: int,
     *     lateDays: int,
     *     absentDays: int,
     *     workedMinutes: int,
     *     lateMinutes: int
     * }>
     */
    private function rows(
        array $employees,
        array $summaries,
        array $names,
        WorkDate $fromDate,
        WorkDate $toDate,
    ): array {
        $daysInRange = $this->dayCount($fromDate, $toDate);
        $byEmployee = [];

        foreach ($summaries as $summary) {
            $id = $summary->employeeProfileId();

            if (! isset($byEmployee[$id])) {
                $byEmployee[$id] = [
                    'presentDays' => 0,
                    'lateDays' => 0,
                    'workedMinutes' => 0,
                    'lateMinutes' => 0,
                    'coveredDays' => 0,
                ];
            }

            $byEmployee[$id]['coveredDays']++;
            $byEmployee[$id]['workedMinutes'] += $summary->workedMinutes();
            $byEmployee[$id]['lateMinutes'] += $summary->lateMinutes();

            if ($summary->status() === AttendanceStatus::Absent) {
                continue;
            }

            $byEmployee[$id]['presentDays']++;

            if ($summary->status() === AttendanceStatus::Late || $summary->lateMinutes() > 0) {
                $byEmployee[$id]['lateDays']++;
            }
        }

        $rows = [];

        foreach ($employees as $employee) {
            $id = $employee->id();
            $stats = $byEmployee[$id] ?? [
                'presentDays' => 0,
                'lateDays' => 0,
                'workedMinutes' => 0,
                'lateMinutes' => 0,
                'coveredDays' => 0,
            ];
            $absentDays = max(0, $daysInRange - $stats['presentDays']);

            $rows[] = [
                'name' => $names[$employee->userId()] ?? 'Empleado',
                'code' => $employee->employeeCode(),
                'presentDays' => $stats['presentDays'],
                'lateDays' => $stats['lateDays'],
                'absentDays' => $absentDays,
                'workedMinutes' => $stats['workedMinutes'],
                'lateMinutes' => $stats['lateMinutes'],
            ];
        }

        return $rows;
    }

    /**
     * @param  list<Employee>  $employees
     * @return array<int, string>
     */
    private function names(CompanyId $companyId, array $employees): array
    {
        $userIds = array_map(fn (Employee $employee): int => $employee->userId(), $employees);

        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->where('company_id', $companyId->value)
            ->whereIn('id', $userIds)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (User $user): array => [(int) $user->id => (string) $user->name])
            ->all();
    }

    /**
     * @return array{0: WorkDate, 1: WorkDate}
     */
    private function range(Request $request, DateTimeZone $zone): array
    {
        $today = new DateTimeImmutable('now', $zone);
        $defaultFrom = $today->modify('-6 days')->format('Y-m-d');
        $defaultTo = $today->format('Y-m-d');
        $from = (string) $request->input('from_date', $defaultFrom);
        $to = (string) $request->input('to_date', $defaultTo);

        return [new WorkDate($from), new WorkDate($to)];
    }

    private function branchId(Request $request): ?int
    {
        if (! $request->filled('branch_id')) {
            return null;
        }

        return $request->integer('branch_id');
    }

    private function dayCount(WorkDate $fromDate, WorkDate $toDate): int
    {
        $from = new DateTimeImmutable($fromDate->value);
        $to = new DateTimeImmutable($toDate->value);

        return ((int) $from->diff($to)->days) + 1;
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
}
