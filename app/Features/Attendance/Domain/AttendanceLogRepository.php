<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Shared\Domain\CompanyId;

interface AttendanceLogRepository
{
    public function save(AttendanceLog $log): AttendanceLog;

    /**
     * Logs for one employee on one civil date, already limited to the company.
     *
     * @return list<AttendanceLog>
     */
    public function forEmployeeOnDate(CompanyId $companyId, int $employeeProfileId, WorkDate $workDate): array;
}
