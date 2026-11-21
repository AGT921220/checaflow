<?php

declare(strict_types=1);

namespace App\Features\Attendance\Infrastructure;

use App\Features\Attendance\Domain\AttendanceLog;
use App\Features\Attendance\Domain\AttendanceLogRepository;
use App\Features\Attendance\Domain\Exceptions\AttendanceLogBelongsToAnotherCompany;
use App\Features\Attendance\Domain\PunchSource;
use App\Features\Attendance\Domain\PunchType;
use App\Features\Attendance\Domain\WorkDate;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\GeoPoint;
use App\Models\AttendanceLog as AttendanceLogRecord;
use DateTimeImmutable;
use DateTimeInterface;

final class EloquentAttendanceLogRepository implements AttendanceLogRepository
{
    public function save(AttendanceLog $log): AttendanceLog
    {
        $record = $this->record($log);
        $record->company_id = $log->companyId()->value;
        $record->employee_profile_id = $log->employeeProfileId();
        $record->branch_id = $log->branchId();
        $record->work_date = $log->workDate()->value;
        $record->punch_type = $log->punchType()->value;
        $record->punched_at = $log->punchedAt();
        $record->latitude = $log->location()?->latitude;
        $record->longitude = $log->location()?->longitude;
        $record->is_within_geofence = $log->isWithinGeofence();
        $record->source = $log->source()->value;
        $record->ip_address = $log->ipAddress();
        $record->notes = $log->notes();
        $record->save();

        return $this->map($record);
    }

    public function forEmployeeOnDate(CompanyId $companyId, int $employeeProfileId, WorkDate $workDate): array
    {
        $records = AttendanceLogRecord::query()
            ->where('company_id', $companyId->value)
            ->where('employee_profile_id', $employeeProfileId)
            ->whereDate('work_date', $workDate->value)
            ->orderBy('punched_at')
            ->orderBy('id')
            ->get();

        return array_values($records->map(fn (AttendanceLogRecord $record): AttendanceLog => $this->map($record))->all());
    }

    private function record(AttendanceLog $log): AttendanceLogRecord
    {
        if ($log->id() === null) {
            return new AttendanceLogRecord;
        }

        $record = AttendanceLogRecord::query()
            ->where('company_id', $log->companyId()->value)
            ->find($log->id());

        if ($record === null) {
            throw new AttendanceLogBelongsToAnotherCompany;
        }

        return $record;
    }

    private function map(AttendanceLogRecord $record): AttendanceLog
    {
        return new AttendanceLog(
            (int) $record->id,
            new CompanyId((int) $record->company_id),
            (int) $record->employee_profile_id,
            (int) $record->branch_id,
            $this->workDate($record->work_date),
            PunchType::from((string) $record->punch_type),
            $this->instant($record->punched_at),
            $this->location($record->latitude, $record->longitude),
            $this->flag($record->is_within_geofence),
            PunchSource::from((string) $record->source),
            $record->ip_address,
            $record->notes,
        );
    }

    private function workDate(mixed $value): WorkDate
    {
        if ($value instanceof DateTimeInterface) {
            return new WorkDate($value->format('Y-m-d'));
        }

        return new WorkDate((string) $value);
    }

    private function instant(mixed $value): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }

    private function location(mixed $latitude, mixed $longitude): ?GeoPoint
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return new GeoPoint((float) $latitude, (float) $longitude);
    }

    private function flag(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }
}
