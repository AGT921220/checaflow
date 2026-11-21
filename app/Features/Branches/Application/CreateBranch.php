<?php

declare(strict_types=1);

namespace App\Features\Branches\Application;

use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\CreateBranchData;
use App\Features\Branches\Domain\Exceptions\BranchLimitExceeded;
use App\Features\Shared\Domain\Exceptions\CompanyNotFound;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CompanyActor;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\Exceptions\InactiveCompanyException;
use App\Features\Users\Domain\PermissionName;

final class CreateBranch
{
    public function __construct(
        private BranchRepository $branches,
        private CompanyRepository $companies,
        private AuthorizeActor $access,
    ) {}

    public function handle(CreateBranchData $command): Branch
    {
        $this->access->handle(
            $command->actorRole,
            $command->actorCompanyId,
            PermissionName::BranchesManage,
            $command->companyId,
        );
        CompanyActor::assertCanOperate($command->actorRole, $command->actorCompanyId, $command->companyId);

        $company = $this->company($command);
        $this->assertWithinBranchLimit($company);

        return $this->branches->save(Branch::open(
            $command->companyId,
            $command->name,
            $command->address,
            $command->location,
            $command->geofenceRadiusMeters,
            $command->requireGeofence,
            $command->isActive,
        ));
    }

    private function company(CreateBranchData $command): Company
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

    private function assertWithinBranchLimit(Company $company): void
    {
        $maxBranches = $company->maxBranches();

        if ($maxBranches === null) {
            return;
        }

        if ($this->branches->countActiveByCompanyId($company->id()) >= $maxBranches) {
            throw new BranchLimitExceeded;
        }
    }
}
