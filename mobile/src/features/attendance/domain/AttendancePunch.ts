import type { PunchType } from "./PunchType.ts";

export interface AttendancePunch {
    readonly punchType: PunchType;
    readonly punchedAt: string;
    readonly latitude: number | null;
    readonly longitude: number | null;
    readonly isWithinGeofence: boolean | null;
}
