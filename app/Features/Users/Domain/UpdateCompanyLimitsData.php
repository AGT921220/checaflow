<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

use App\Features\Shared\Domain\CompanyId;

final readonly class UpdateCompanyLimitsData
{
    public function __construct(
        public UserRole $actorRole,
        public CompanyId $companyId,
        public int $maxEmployees,
        public ?int $maxBranches,
    ) {}
}
