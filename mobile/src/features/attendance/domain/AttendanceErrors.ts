import { DomainError } from "../../shared/domain/DomainError.ts";
import type { PunchType } from "./PunchType.ts";

export class DuplicatePunch extends DomainError {
    constructor(type: PunchType) {
        super(`Punch ${type} is already registered for this work date.`);
    }
}

export class InvalidPunchSequence extends DomainError {
    constructor(expected: PunchType, actual: PunchType) {
        super(`Expected ${expected} but received ${actual}.`);
    }
}

export class AttendanceDayAlreadyClosed extends DomainError {
    constructor() {
        super("This work date already has a clock-out punch.");
    }
}

export class InconsistentAttendanceDay extends DomainError {
    constructor() {
        super("Attendance punches skip a step in the daily cycle.");
    }
}

export class PunchLocationRequired extends DomainError {
    constructor() {
        super("This branch requires a location to register a punch.");
    }
}

export class PunchOutsideGeofence extends DomainError {
    constructor() {
        super("Punch is outside the branch geofence.");
    }
}

export class GeofenceIsNotConfigured extends DomainError {
    constructor() {
        super("Branch requires a geofence but it is not configured.");
    }
}

export class AttendanceBelongsToAnotherCompany extends DomainError {
    constructor() {
        super("Attendance data belongs to another company.");
    }
}

export class NotesTooLong extends DomainError {
    constructor() {
        super("Notes cannot be longer than 2000 characters.");
    }
}
