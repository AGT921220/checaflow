<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

use App\Features\Shared\Domain\CompanyId;

interface CompanyRepository
{
    public function find(CompanyId $companyId): ?Company;

    public function slugExists(string $slug): bool;

    public function save(Company $company): Company;
}
