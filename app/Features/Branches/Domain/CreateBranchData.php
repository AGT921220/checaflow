<?php

declare(strict_types=1);

namespace App\Features\Branches\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\GeoPoint;
use App\Features\Users\Domain\UserRole;

final readonly class CreateBranchData
{
    public function __construct(
        public UserRole $actorRole,
        public CompanyId $actorCompanyId,
        public CompanyId $companyId,
        public string $name,
        public ?string $address,
        public ?GeoPoint $location,
        public ?int $geofenceRadiusMeters,
        public bool $requireGeofence,
        public bool $isActive = true,
    ) {}
}
