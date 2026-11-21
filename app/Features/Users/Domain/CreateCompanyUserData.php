<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

use App\Features\Shared\Domain\CompanyId;

final readonly class CreateCompanyUserData
{
    public function __construct(
        public UserRole $actorRole,
        public CompanyId $actorCompanyId,
        public string $name,
        public string $email,
        public UserRole $role,
        public bool $isActive,
    ) {}
}
