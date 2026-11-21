<?php

declare(strict_types=1);

namespace App\Features\Users\Application;

use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\CreateCompanyData;
use App\Features\Users\Domain\Exceptions\PlanNotFound;
use App\Features\Users\Domain\Exceptions\SlugAlreadyUsed;
use App\Features\Users\Domain\PermissionName;
use App\Features\Users\Domain\PlanRepository;

final class CreateCompany
{
    public function __construct(
        private CompanyRepository $companies,
        private PlanRepository $plans,
        private AuthorizeActor $access,
    ) {}

    public function handle(CreateCompanyData $command): Company
    {
        $this->access->handle($command->actorRole, null, PermissionName::CompaniesManage);

        $plan = $this->plans->find($command->planId);

        if ($plan === null) {
            throw new PlanNotFound;
        }

        $plan->assertAllows($command->maxEmployees);

        $company = Company::open(
            $command->name,
            $command->slug,
            $command->taxId,
            $plan->id(),
            $plan->code(),
            $command->maxEmployees,
            $command->maxBranches,
            $command->isActive,
            $command->timezone,
        );

        if ($this->companies->slugExists($company->slug())) {
            throw new SlugAlreadyUsed;
        }

        return $this->companies->save($company);
    }
}
