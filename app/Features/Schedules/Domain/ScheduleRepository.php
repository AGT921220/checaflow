<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\PagedResult;
use App\Features\Shared\Domain\PageQuery;

interface ScheduleRepository
{
    public function save(Schedule $schedule): Schedule;

    public function find(CompanyId $companyId, int $scheduleId): ?Schedule;

    /**
     * @return list<Schedule>
     */
    public function search(SearchSchedulesCriteria $criteria): array;

    public function paginate(SearchSchedulesCriteria $criteria, PageQuery $page): PagedResult;
}
