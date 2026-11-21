<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

final readonly class CreateCompanyData
{
    public function __construct(
        public UserRole $actorRole,
        public string $name,
        public string $slug,
        public ?string $taxId,
        public int $planId,
        public int $maxEmployees,
        public bool $isActive,
        public string $timezone,
        public ?int $maxBranches = 2,
    ) {}
}
