<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\PagedResult;

interface DailyAttendanceRepository
{
    public function save(DailyAttendance $summary): DailyAttendance;

    public function find(CompanyId $companyId, int $employeeProfileId, WorkDate $workDate): ?DailyAttendance;

    public function search(CompanyDailyReportCriteria $criteria): PagedResult;

    public function searchRange(CompanyAttendanceRangeCriteria $criteria): PagedResult;
}
