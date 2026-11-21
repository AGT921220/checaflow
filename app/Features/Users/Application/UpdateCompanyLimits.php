<?php

declare(strict_types=1);

namespace App\Features\Users\Application;

use App\Features\Branches\Domain\BranchRepository;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Exceptions\CompanyNotFound;
use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\Exceptions\BranchLimitBelowActiveCount;
use App\Features\Users\Domain\Exceptions\InactiveCompanyException;
use App\Features\Users\Domain\Exceptions\PlanNotFound;
use App\Features\Users\Domain\PermissionName;
use App\Features\Users\Domain\PlanRepository;
use App\Features\Users\Domain\UpdateCompanyLimitsData;

final class UpdateCompanyLimits
{
    public function __construct(
        private CompanyRepository $companies,
        private PlanRepository $plans,
        private BranchRepository $branches,
        private AuthorizeActor $access,
    ) {}

    public function handle(UpdateCompanyLimitsData $command): Company
    {
        $this->access->handle($command->actorRole, null, PermissionName::CompaniesManage);

        $company = $this->companies->find($command->companyId);

        if ($company === null) {
            throw new CompanyNotFound;
        }

        if (! $company->isActive()) {
            throw new InactiveCompanyException;
        }

        $plan = $this->plans->find($company->planId());

        if ($plan === null) {
            throw new PlanNotFound;
        }

        $plan->assertAllows($command->maxEmployees);
        $this->assertBranchCap($command->companyId, $command->maxBranches);

        return $this->companies->save($company->withLimits(
            $command->maxEmployees,
            $command->maxBranches,
        ));
    }

    private function assertBranchCap(CompanyId $companyId, ?int $maxBranches): void
    {
        if ($maxBranches === null) {
            return;
        }

        if ($this->branches->countActiveByCompanyId($companyId) > $maxBranches) {
            throw new BranchLimitBelowActiveCount;
        }
    }
}
