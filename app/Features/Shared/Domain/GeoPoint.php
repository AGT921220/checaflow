<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain;

use App\Features\Shared\Domain\Exceptions\InvalidGeoPoint;

final readonly class GeoPoint
{
    private const EARTH_RADIUS_METERS = 6371000;

    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if ($this->latitude < -90 || $this->latitude > 90) {
            throw new InvalidGeoPoint('La latitud debe estar entre -90 y 90.');
        }

        if ($this->longitude < -180 || $this->longitude > 180) {
            throw new InvalidGeoPoint('La longitud debe estar entre -180 y 180.');
        }
    }

    public function distanceInMeters(self $other): float
    {
        $latFrom = deg2rad($this->latitude);
        $latTo = deg2rad($other->latitude);
        $latDelta = deg2rad($other->latitude - $this->latitude);
        $lonDelta = deg2rad($other->longitude - $this->longitude);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }
}
