<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Attendance\Domain\Exceptions\GeofenceIsNotConfigured;
use App\Features\Attendance\Domain\Exceptions\PunchLocationRequired;
use App\Features\Attendance\Domain\Exceptions\PunchOutsideGeofence;
use App\Features\Shared\Domain\GeoPoint;

final readonly class GeofenceCheck
{
    public function __construct(
        private bool $required,
        private ?GeoPoint $center,
        private ?int $radiusMeters,
    ) {}

    public function match(?GeoPoint $point): ?bool
    {
        if (! $this->required) {
            return null;
        }

        if ($this->center === null || $this->radiusMeters === null) {
            throw new GeofenceIsNotConfigured;
        }

        if ($point === null) {
            throw new PunchLocationRequired;
        }

        if ($this->center->distanceInMeters($point) > $this->radiusMeters) {
            throw new PunchOutsideGeofence;
        }

        return true;
    }
}
