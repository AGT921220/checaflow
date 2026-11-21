<?php

declare(strict_types=1);

namespace App\Features\Branches\Domain;

use App\Features\Branches\Domain\Exceptions\BranchAddressTooLong;
use App\Features\Branches\Domain\Exceptions\BranchIsNotPersisted;
use App\Features\Branches\Domain\Exceptions\BranchNameRequired;
use App\Features\Branches\Domain\Exceptions\BranchNameTooLong;
use App\Features\Branches\Domain\Exceptions\GeofenceIsNotConfigured;
use App\Features\Branches\Domain\Exceptions\InvalidGeofenceRadius;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\GeoPoint;

final readonly class Branch
{
    public function __construct(
        private ?int $id,
        private CompanyId $companyId,
        private string $name,
        private ?string $address,
        private ?GeoPoint $location,
        private ?int $geofenceRadiusMeters,
        private bool $requireGeofence,
        private bool $isActive,
    ) {
        if ($this->id !== null && $this->id < 1) {
            throw new BranchIsNotPersisted;
        }

        $this->assertName($this->name);
        $this->assertAddress($this->address);
        $this->assertRadius($this->geofenceRadiusMeters);
        $this->assertGeofence($this->location, $this->geofenceRadiusMeters, $this->requireGeofence);
    }

    public static function open(
        CompanyId $companyId,
        string $name,
        ?string $address,
        ?GeoPoint $location,
        ?int $geofenceRadiusMeters,
        bool $requireGeofence,
        bool $isActive = true,
    ): self {
        return new self(
            null,
            $companyId,
            trim($name),
            self::blankToNull($address),
            $location,
            $geofenceRadiusMeters,
            $requireGeofence,
            $isActive,
        );
    }

    public function id(): int
    {
        if ($this->id === null) {
            throw new BranchIsNotPersisted;
        }

        return $this->id;
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function belongsTo(CompanyId $companyId): bool
    {
        return $this->companyId->equals($companyId);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function location(): ?GeoPoint
    {
        return $this->location;
    }

    public function geofenceRadiusMeters(): ?int
    {
        return $this->geofenceRadiusMeters;
    }

    public function requiresGeofence(): bool
    {
        return $this->requireGeofence;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->companyId,
            $this->name,
            $this->address,
            $this->location,
            $this->geofenceRadiusMeters,
            $this->requireGeofence,
            $this->isActive,
        );
    }

    public function revise(
        string $name,
        ?string $address,
        ?GeoPoint $location,
        ?int $geofenceRadiusMeters,
        bool $requireGeofence,
        bool $isActive,
    ): self {
        return new self(
            $this->id,
            $this->companyId,
            trim($name),
            self::blankToNull($address),
            $location,
            $geofenceRadiusMeters,
            $requireGeofence,
            $isActive,
        );
    }

    private function assertName(string $name): void
    {
        if ($name === '') {
            throw new BranchNameRequired;
        }

        if (mb_strlen($name) > 255) {
            throw new BranchNameTooLong;
        }
    }

    private function assertAddress(?string $address): void
    {
        if ($address === null) {
            return;
        }

        if (mb_strlen($address) > 255) {
            throw new BranchAddressTooLong;
        }
    }

    private function assertRadius(?int $radius): void
    {
        if ($radius === null) {
            return;
        }

        if ($radius < 1) {
            throw new InvalidGeofenceRadius;
        }
    }

    private function assertGeofence(?GeoPoint $location, ?int $radius, bool $required): void
    {
        if (! $required) {
            return;
        }

        if ($location === null || $radius === null) {
            throw new GeofenceIsNotConfigured;
        }
    }

    private static function blankToNull(?string $address): ?string
    {
        if ($address === null) {
            return null;
        }

        $address = trim($address);

        if ($address === '') {
            return null;
        }

        return $address;
    }
}
