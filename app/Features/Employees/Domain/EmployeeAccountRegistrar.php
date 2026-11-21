<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Shared\Domain\CompanyId;

interface EmployeeAccountRegistrar
{
    public function register(
        CompanyId $companyId,
        string $name,
        string $nickname,
        ?string $email,
        ?string $phone,
        string $password,
    ): int;
}
