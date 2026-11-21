<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Attendance\Domain\Exceptions\InvalidAttendanceRange;
use App\Features\Attendance\Domain\Exceptions\InvalidBranchId;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Sort;
use App\Features\Users\Domain\UserRole;
use DateTimeImmutable;

final readonly class CompanyAttendanceRangeCriteria
{
    public const MAX_DAYS = 92;

    public Sort $sort;

    public function __construct(
        public UserRole $actorRole,
        public ?CompanyId $actorCompanyId,
        public CompanyId $companyId,
        public WorkDate $fromDate,
        public WorkDate $toDate,
        public ?int $branchId = null,
        string $sortColumn = 'work_date',
        string $sortDirection = 'asc',
    ) {
        if ($this->branchId !== null && $this->branchId < 1) {
            throw new InvalidBranchId;
        }

        $from = new DateTimeImmutable($this->fromDate->value);
        $to = new DateTimeImmutable($this->toDate->value);

        if ($from > $to) {
            throw new InvalidAttendanceRange('La fecha inicial no puede ser posterior a la final.');
        }

        $days = (int) $from->diff($to)->days;

        if ($days > self::MAX_DAYS) {
            throw new InvalidAttendanceRange('El rango no puede superar '.self::MAX_DAYS.' días.');
        }

        $this->sort = new Sort($sortColumn, $sortDirection, [
            'work_date',
            'employee_profile_id',
            'status',
            'late_minutes',
            'worked_minutes',
        ]);
    }
}
