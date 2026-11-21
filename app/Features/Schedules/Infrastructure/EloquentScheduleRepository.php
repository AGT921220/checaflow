<?php

declare(strict_types=1);

namespace App\Features\Schedules\Infrastructure;

use App\Features\Schedules\Domain\Exceptions\InvalidWorkingDay;
use App\Features\Schedules\Domain\Exceptions\ScheduleIsNotPersisted;
use App\Features\Schedules\Domain\Exceptions\ScheduleNotFound;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Schedules\Domain\TimeOfDay;
use App\Features\Schedules\Domain\WorkingDays;
use App\Features\Shared\Domain\CompanyId;
use App\Models\Schedule as ScheduleRecord;

final class EloquentScheduleRepository implements ScheduleRepository
{
    public function save(Schedule $schedule): Schedule
    {
        $record = $this->record($schedule);
        $record->company_id = $schedule->companyId()->value;
        $record->name = $schedule->name();
        $record->clock_in_time = $this->clock($schedule->clockIn());
        $record->clock_out_time = $this->clock($schedule->clockOut());
        $record->late_tolerance_minutes = $schedule->lateToleranceMinutes();
        $record->allowed_lunch_minutes = $schedule->allowedLunchMinutes();
        $record->working_days = $schedule->workingDays()->values();
        $record->is_active = $schedule->isActive();
        $record->save();

        return $this->map($record);
    }

    public function find(CompanyId $companyId, int $scheduleId): ?Schedule
    {
        $record = ScheduleRecord::query()
            ->where('company_id', $companyId->value)
            ->find($scheduleId);

        if ($record === null) {
            return null;
        }

        return $this->map($record);
    }

    public function search(SearchSchedulesCriteria $criteria): array
    {
        $query = ScheduleRecord::query()->where('company_id', $criteria->companyId->value);

        if ($criteria->isActive !== null) {
            $query->where('is_active', $criteria->isActive);
        }

        $query->orderBy($criteria->sort->column, $criteria->sort->direction);

        return array_values($query->get()->map(fn (ScheduleRecord $record): Schedule => $this->map($record))->all());
    }

    private function record(Schedule $schedule): ScheduleRecord
    {
        $id = $this->persistedId($schedule);

        if ($id === null) {
            return new ScheduleRecord;
        }

        $record = ScheduleRecord::query()
            ->where('company_id', $schedule->companyId()->value)
            ->find($id);

        if ($record === null) {
            throw new ScheduleNotFound;
        }

        return $record;
    }

    private function persistedId(Schedule $schedule): ?int
    {
        try {
            return $schedule->id();
        } catch (ScheduleIsNotPersisted) {
            return null;
        }
    }

    private function map(ScheduleRecord $record): Schedule
    {
        return new Schedule(
            (int) $record->id,
            new CompanyId((int) $record->company_id),
            $record->name,
            TimeOfDay::fromString((string) $record->clock_in_time),
            TimeOfDay::fromString((string) $record->clock_out_time),
            (int) $record->late_tolerance_minutes,
            (int) $record->allowed_lunch_minutes,
            WorkingDays::fromStrings($this->days($record->working_days)),
            (bool) $record->is_active,
        );
    }

    /**
     * @return list<string>
     */
    private function days(mixed $value): array
    {
        if (! is_array($value)) {
            throw new InvalidWorkingDay('working_days');
        }

        $days = [];

        foreach ($value as $day) {
            if (! is_string($day)) {
                throw new InvalidWorkingDay('working_days');
            }

            $days[] = $day;
        }

        return $days;
    }

    private function clock(TimeOfDay $time): string
    {
        return sprintf('%02d:%02d:00', $time->hour(), $time->minute());
    }
}
