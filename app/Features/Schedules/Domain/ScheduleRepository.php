<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain;

use App\Features\Shared\Domain\CompanyId;

interface ScheduleRepository
{
    public function save(Schedule $schedule): Schedule;

    public function find(CompanyId $companyId, int $scheduleId): ?Schedule;

    /**
     * @return list<Schedule>
     */
    public function search(SearchSchedulesCriteria $criteria): array;
}
