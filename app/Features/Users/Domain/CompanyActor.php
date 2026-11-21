<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\Exceptions\ActorCompanyMismatch;
use App\Features\Users\Domain\Exceptions\ActorCompanyRequired;
use App\Features\Users\Domain\Exceptions\ForbiddenUserRole;

final readonly class CompanyActor
{
    public function __construct(
        public UserRole $actorRole,
        public ?CompanyId $actorCompanyId,
        public ?int $employeeProfileId = null,
    ) {
        if ($this->actorRole !== UserRole::SuperAdmin && $this->actorCompanyId === null) {
            throw new ActorCompanyRequired;
        }
    }

    public function assertCompany(CompanyId $targetCompanyId): void
    {
        if ($this->actorCompanyId === null || ! $this->actorCompanyId->equals($targetCompanyId)) {
            throw new ActorCompanyMismatch;
        }
    }

    public static function assertCanOperate(UserRole $actorRole, CompanyId $actorCompanyId, CompanyId $targetCompanyId): void
    {
        if (! $actorRole->managesCompany()) {
            throw new ForbiddenUserRole;
        }

        if (! $actorCompanyId->equals($targetCompanyId)) {
            throw new ActorCompanyMismatch;
        }
    }
}
