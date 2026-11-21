<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Attendance;

use App\Features\Attendance\Domain\Exceptions\PunchLocationRequired;
use App\Features\Attendance\Domain\Exceptions\PunchOutsideGeofence;
use App\Features\Attendance\Domain\GeofenceCheck;
use App\Features\Shared\Domain\GeoPoint;
use PHPUnit\Framework\TestCase;

final class GeofenceCheckTest extends TestCase
{
    public function test_an_optional_geofence_does_not_need_a_location(): void
    {
        $result = (new GeofenceCheck(false, null, null))->match(null);

        $this->assertNull($result);
    }

    public function test_a_punch_inside_the_radius_is_accepted(): void
    {
        $center = new GeoPoint(19.432608, -99.133209);
        $result = (new GeofenceCheck(true, $center, 100))->match($center);

        $this->assertTrue($result);
    }

    public function test_a_punch_outside_the_radius_is_rejected(): void
    {
        $center = new GeoPoint(19.432608, -99.133209);

        $this->expectException(PunchOutsideGeofence::class);

        (new GeofenceCheck(true, $center, 100))->match(new GeoPoint(19.442608, -99.133209));
    }

    public function test_a_required_geofence_needs_a_location(): void
    {
        $this->expectException(PunchLocationRequired::class);

        (new GeofenceCheck(true, new GeoPoint(19.432608, -99.133209), 100))->match(null);
    }
}
