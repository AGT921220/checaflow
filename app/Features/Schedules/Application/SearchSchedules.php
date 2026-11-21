<?php

declare(strict_types=1);

namespace App\Features\Schedules\Application;

use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Shared\Domain\PagedResult;
use App\Features\Shared\Domain\PageQuery;

final class SearchSchedules
{
    public function __construct(private ScheduleRepository $schedules) {}

    /**
     * @return list<Schedule>
     */
    public function handle(SearchSchedulesCriteria $criteria): array
    {
        return $this->schedules->search($criteria);
    }

    public function page(SearchSchedulesCriteria $criteria, PageQuery $page): PagedResult
    {
        return $this->schedules->paginate($criteria, $page);
    }
}
