<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

use App\Features\Shared\Domain\CompanyId;

final readonly class UpdateOwnProfilePhotoData
{
    public function __construct(
        public UserRole $actorRole,
        public CompanyId $actorCompanyId,
        public int $actorUserId,
        public string $binary,
        public string $extension,
    ) {}
}
