<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

use App\Features\Shared\Domain\CompanyId;

final readonly class CreateCompanyAdminData
{
    public function __construct(
        public UserRole $actorRole,
        public CompanyId $targetCompanyId,
        public string $firstName,
        public string $lastName,
        public string $nickname,
        public ?string $email,
        public ?string $phone,
        public string $plainPassword,
    ) {}
}
