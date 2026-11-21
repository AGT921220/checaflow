<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

final readonly class OpenCompanyAccountData
{
    public function __construct(
        public UserRole $actorRole,
        public string $companyName,
        public int $planId,
        public int $maxEmployees,
        public string $firstName,
        public string $lastName,
        public string $nickname,
        public ?string $email,
        public ?string $phone,
        public string $plainPassword,
    ) {}
}
