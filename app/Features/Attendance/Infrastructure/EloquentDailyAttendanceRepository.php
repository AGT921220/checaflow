<?php

declare(strict_types=1);

namespace App\Features\Attendance\Infrastructure;

use App\Features\Attendance\Domain\AttendanceStatus;
use App\Features\Attendance\Domain\CompanyAttendanceRangeCriteria;
use App\Features\Attendance\Domain\CompanyDailyReportCriteria;
use App\Features\Attendance\Domain\DailyAttendance;
use App\Features\Attendance\Domain\DailyAttendanceRepository;
use App\Features\Attendance\Domain\WorkDate;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\PagedResult;
use App\Models\DailyAttendanceSummary;
use DateTimeImmutable;
use DateTimeInterface;

final class EloquentDailyAttendanceRepository implements DailyAttendanceRepository
{
    public function save(DailyAttendance $summary): DailyAttendance
    {
        $record = $this->record($summary);
        $record->company_id = $summary->companyId()->value;
        $record->employee_profile_id = $summary->employeeProfileId();
        $record->branch_id = $summary->branchId();
        $record->schedule_id = $summary->scheduleId();
        $record->work_date = $summary->workDate()->value;
        $record->clock_in_at = $summary->clockInAt();
        $record->lunch_out_at = $summary->lunchOutAt();
        $record->lunch_in_at = $summary->lunchInAt();
        $record->clock_out_at = $summary->clockOutAt();
        $record->late_minutes = $summary->lateMinutes();
        $record->lunch_minutes = $summary->lunchMinutes();
        $record->excess_lunch_minutes = $summary->excessLunchMinutes();
        $record->worked_minutes = $summary->workedMinutes();
        $record->status = $summary->status()->value;
        $record->save();

        return $this->map($record);
    }

    public function find(CompanyId $companyId, int $employeeProfileId, WorkDate $workDate): ?DailyAttendance
    {
        $record = DailyAttendanceSummary::query()
            ->where('company_id', $companyId->value)
            ->where('employee_profile_id', $employeeProfileId)
            ->whereDate('work_date', $workDate->value)
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->map($record);
    }

    public function search(CompanyDailyReportCriteria $criteria): PagedResult
    {
        $query = DailyAttendanceSummary::query()
            ->where('company_id', $criteria->companyId->value)
            ->whereDate('work_date', $criteria->workDate->value);

        if ($criteria->branchId !== null) {
            $query->where('branch_id', $criteria->branchId);
        }

        if ($criteria->employeeProfileIds !== null) {
            $query->whereIn('employee_profile_id', $criteria->employeeProfileIds);
        }

        $query->orderBy($criteria->sort->column, $criteria->sort->direction);
        $total = (clone $query)->count();
        $items = $query
            ->offset($criteria->offset())
            ->limit($criteria->perPage)
            ->get()
            ->map(fn (DailyAttendanceSummary $record): DailyAttendance => $this->map($record))
            ->all();

        return new PagedResult(array_values($items), $total, $criteria->page, $criteria->perPage);
    }

    public function searchRange(CompanyAttendanceRangeCriteria $criteria): PagedResult
    {
        $query = DailyAttendanceSummary::query()
            ->where('company_id', $criteria->companyId->value)
            ->whereDate('work_date', '>=', $criteria->fromDate->value)
            ->whereDate('work_date', '<=', $criteria->toDate->value);

        if ($criteria->branchId !== null) {
            $query->where('branch_id', $criteria->branchId);
        }

        if ($criteria->employeeProfileIds !== null) {
            $query->whereIn('employee_profile_id', $criteria->employeeProfileIds);
        }

        $query->orderBy($criteria->sort->column, $criteria->sort->direction);
        $total = (clone $query)->count();
        $items = $query
            ->offset($criteria->offset())
            ->limit($criteria->perPage)
            ->get()
            ->map(fn (DailyAttendanceSummary $record): DailyAttendance => $this->map($record))
            ->all();

        return new PagedResult(array_values($items), $total, $criteria->page, $criteria->perPage);
    }

    private function record(DailyAttendance $summary): DailyAttendanceSummary
    {
        $query = DailyAttendanceSummary::query()
            ->where('company_id', $summary->companyId()->value)
            ->where('employee_profile_id', $summary->employeeProfileId())
            ->whereDate('work_date', $summary->workDate()->value);

        if ($summary->id() !== null) {
            $query->whereKey($summary->id());
        }

        $record = $query->first();

        if ($record === null) {
            return new DailyAttendanceSummary;
        }

        return $record;
    }

    private function map(DailyAttendanceSummary $record): DailyAttendance
    {
        return new DailyAttendance(
            (int) $record->id,
            new CompanyId((int) $record->company_id),
            (int) $record->employee_profile_id,
            (int) $record->branch_id,
            (int) $record->schedule_id,
            $this->workDate($record->work_date),
            $this->instant($record->clock_in_at),
            $this->instant($record->lunch_out_at),
            $this->instant($record->lunch_in_at),
            $this->instant($record->clock_out_at),
            (int) $record->late_minutes,
            (int) $record->lunch_minutes,
            (int) $record->excess_lunch_minutes,
            (int) $record->worked_minutes,
            AttendanceStatus::from((string) $record->status),
        );
    }

    private function workDate(mixed $value): WorkDate
    {
        if ($value instanceof DateTimeInterface) {
            return new WorkDate($value->format('Y-m-d'));
        }

        return new WorkDate((string) $value);
    }

    private function instant(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
