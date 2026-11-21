import { DomainError } from "./DomainError.ts";

const EARTH_RADIUS_METERS = 6_371_000;

export class InvalidGeoPoint extends DomainError {
    constructor(message: string) {
        super(message);
    }
}

export class GeoPoint {
    constructor(
        readonly latitude: number,
        readonly longitude: number,
    ) {
        if (this.latitude < -90 || this.latitude > 90) {
            throw new InvalidGeoPoint("Latitude must be between -90 and 90.");
        }

        if (this.longitude < -180 || this.longitude > 180) {
            throw new InvalidGeoPoint("Longitude must be between -180 and 180.");
        }
    }

    distanceInMeters(other: GeoPoint): number {
        const latFrom = degreesToRadians(this.latitude);
        const latTo = degreesToRadians(other.latitude);
        const latDelta = degreesToRadians(other.latitude - this.latitude);
        const lonDelta = degreesToRadians(other.longitude - this.longitude);
        const a =
            Math.sin(latDelta / 2) ** 2 +
            Math.cos(latFrom) * Math.cos(latTo) * Math.sin(lonDelta / 2) ** 2;

        return EARTH_RADIUS_METERS * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    }
}

function degreesToRadians(degrees: number): number {
    return (degrees * Math.PI) / 180;
}
