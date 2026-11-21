<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\Exceptions\InvalidUserReference;

final readonly class ToggleUserStatusData
{
    public function __construct(
        public UserRole $actorRole,
        public CompanyId $actorCompanyId,
        public int $userId,
        public bool $isActive,
    ) {
        if ($this->userId < 1) {
            throw new InvalidUserReference;
        }
    }
}
