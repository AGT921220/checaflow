<?php

declare(strict_types=1);

namespace App\Features\Users\Application;

use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CreateCompanyAdminData;
use App\Features\Users\Domain\CreateCompanyData;
use App\Features\Users\Domain\OpenCompanyAccountData;
use App\Features\Users\Domain\PlatformUser;

final class OpenCompanyAccount
{
    public function __construct(
        private CreateCompany $createCompany,
        private CreateCompanyAdmin $createCompanyAdmin,
    ) {}

    /**
     * @return array{company: Company, admin: PlatformUser}
     */
    public function handle(OpenCompanyAccountData $command): array
    {
        $slug = Company::slugFromName($command->companyName);

        $company = $this->createCompany->handle(new CreateCompanyData(
            $command->actorRole,
            $command->companyName,
            $slug,
            null,
            $command->planId,
            $command->maxEmployees,
            true,
            'America/Mexico_City',
        ));

        $admin = $this->createCompanyAdmin->handle(new CreateCompanyAdminData(
            $command->actorRole,
            $company->id(),
            $command->firstName,
            $command->lastName,
            $command->nickname,
            $command->email,
            $command->phone,
            $command->plainPassword,
        ));

        return [
            'company' => $company,
            'admin' => $admin,
        ];
    }
}
