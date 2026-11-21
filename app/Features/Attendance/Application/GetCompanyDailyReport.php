<?php

declare(strict_types=1);

namespace App\Features\Attendance\Application;

use App\Features\Attendance\Domain\CompanyDailyReportCriteria;
use App\Features\Attendance\Domain\DailyAttendance;
use App\Features\Attendance\Domain\DailyAttendanceRepository;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\Exceptions\BranchDoesNotBelongToCompany;
use App\Features\Branches\Domain\Exceptions\BranchNotFound;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\PermissionName;

final class GetCompanyDailyReport
{
    public function __construct(
        private DailyAttendanceRepository $summaries,
        private BranchRepository $branches,
        private AuthorizeActor $access,
    ) {}

    /**
     * @return list<DailyAttendance>
     */
    public function handle(CompanyDailyReportCriteria $criteria): array
    {
        $this->access->handle(
            $criteria->actorRole,
            $criteria->actorCompanyId,
            PermissionName::AttendanceViewCompanyReport,
            $criteria->companyId,
        );
        $this->assertBranch($criteria);

        return $this->summaries->search($criteria);
    }

    private function assertBranch(CompanyDailyReportCriteria $criteria): void
    {
        if ($criteria->branchId === null) {
            return;
        }

        $branch = $this->branches->find($criteria->companyId, $criteria->branchId);

        if ($branch === null) {
            throw new BranchNotFound;
        }

        if (! $branch->belongsTo($criteria->companyId)) {
            throw new BranchDoesNotBelongToCompany;
        }
    }
}
