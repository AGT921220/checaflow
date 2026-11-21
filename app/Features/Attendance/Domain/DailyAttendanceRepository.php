<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Shared\Domain\CompanyId;

interface DailyAttendanceRepository
{
    public function save(DailyAttendance $summary): DailyAttendance;

    public function find(CompanyId $companyId, int $employeeProfileId, WorkDate $workDate): ?DailyAttendance;

    /**
     * Rows for one company and work date. Optional branch filter comes from the criteria.
     * Sort column is already whitelisted.
     *
     * @return list<DailyAttendance>
     */
    public function search(CompanyDailyReportCriteria $criteria): array;

    /**
     * Rows for one company between two work dates inclusive. Optional branch filter.
     *
     * @return list<DailyAttendance>
     */
    public function searchRange(CompanyAttendanceRangeCriteria $criteria): array;
}
