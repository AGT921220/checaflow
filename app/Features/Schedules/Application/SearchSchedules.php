<?php

declare(strict_types=1);

namespace App\Features\Schedules\Application;

use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Shared\Domain\PagedResult;

final class SearchSchedules
{
    public function __construct(private ScheduleRepository $schedules) {}

    public function handle(SearchSchedulesCriteria $criteria): PagedResult
    {
        return $this->schedules->search($criteria);
    }
}
