import type { GeoPoint } from "../../shared/domain/GeoPoint.ts";
import { GeofenceIsNotConfigured, PunchLocationRequired, PunchOutsideGeofence } from "./AttendanceErrors.ts";

export interface BranchGeofence {
    readonly required: boolean;
    readonly center: GeoPoint | null;
    readonly radiusMeters: number | null;
}

export class GeofenceCheck {
    constructor(private readonly fence: BranchGeofence) {}

    match(point: GeoPoint | null): void {
        if (!this.fence.required) {
            return;
        }

        if (this.fence.center === null || this.fence.radiusMeters === null || this.fence.radiusMeters < 1) {
            throw new GeofenceIsNotConfigured();
        }

        if (point === null) {
            throw new PunchLocationRequired();
        }

        if (this.fence.center.distanceInMeters(point) > this.fence.radiusMeters) {
            throw new PunchOutsideGeofence();
        }
    }
}
