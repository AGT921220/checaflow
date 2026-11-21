<?php

declare(strict_types=1);

namespace App\Features\Branches\Application;

use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\Exceptions\BranchDoesNotBelongToCompany;
use App\Features\Branches\Domain\Exceptions\BranchLimitExceeded;
use App\Features\Branches\Domain\Exceptions\BranchNotFound;
use App\Features\Branches\Domain\UpdateBranchData;
use App\Features\Shared\Domain\Exceptions\CompanyNotFound;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CompanyActor;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\Exceptions\InactiveCompanyException;
use App\Features\Users\Domain\PermissionName;

final class UpdateBranch
{
    public function __construct(
        private BranchRepository $branches,
        private CompanyRepository $companies,
        private AuthorizeActor $access,
    ) {}

    public function handle(UpdateBranchData $command): Branch
    {
        $this->access->handle(
            $command->actorRole,
            $command->actorCompanyId,
            PermissionName::BranchesManage,
            $command->companyId,
        );
        CompanyActor::assertCanOperate($command->actorRole, $command->actorCompanyId, $command->companyId);

        $company = $this->company($command);
        $branch = $this->branch($command);
        $this->assertActivationWithinLimit($company, $branch, $command->isActive);

        return $this->branches->save($branch->revise(
            $command->name,
            $command->address,
            $command->location,
            $command->geofenceRadiusMeters,
            $command->requireGeofence,
            $command->isActive,
        ));
    }

    private function company(UpdateBranchData $command): Company
    {
        $company = $this->companies->find($command->companyId);

        if ($company === null) {
            throw new CompanyNotFound;
        }

        if (! $company->isActive()) {
            throw new InactiveCompanyException;
        }

        return $company;
    }

    private function branch(UpdateBranchData $command): Branch
    {
        $branch = $this->branches->find($command->companyId, $command->branchId);

        if ($branch === null) {
            throw new BranchNotFound;
        }

        if (! $branch->belongsTo($command->companyId)) {
            throw new BranchDoesNotBelongToCompany;
        }

        return $branch;
    }

    private function assertActivationWithinLimit(Company $company, Branch $branch, bool $willBeActive): void
    {
        if ($branch->isActive() || ! $willBeActive) {
            return;
        }

        $maxBranches = $company->maxBranches();

        if ($maxBranches === null) {
            return;
        }

        if ($this->branches->countActiveByCompanyId($company->id()) >= $maxBranches) {
            throw new BranchLimitExceeded;
        }
    }
}
