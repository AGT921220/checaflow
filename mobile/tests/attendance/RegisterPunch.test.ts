import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { RequireSession } from "../../src/features/auth/application/RequireSession.ts";
import { SessionCompanyMismatch } from "../../src/features/auth/domain/AuthErrors.ts";
import { Session } from "../../src/features/auth/domain/Session.ts";
import type { SessionStore } from "../../src/features/auth/domain/SessionStore.ts";
import { RegisterPunch } from "../../src/features/attendance/application/RegisterPunch.ts";
import { AttendanceStatus } from "../../src/features/attendance/domain/AttendanceStatus.ts";
import type { BranchGeofenceReader } from "../../src/features/attendance/domain/BranchGeofenceReader.ts";
import type { BranchGeofence } from "../../src/features/attendance/domain/GeofenceCheck.ts";
import { InvalidPunchSequence, PunchOutsideGeofence } from "../../src/features/attendance/domain/AttendanceErrors.ts";
import type { PunchCommand, PunchWriter } from "../../src/features/attendance/domain/PunchWriter.ts";
import { PunchType } from "../../src/features/attendance/domain/PunchType.ts";
import { RegisterPunchData } from "../../src/features/attendance/domain/RegisterPunchData.ts";
import { TodayEmployeeStatus } from "../../src/features/attendance/domain/TodayEmployeeStatus.ts";
import type { TodayStatusReader } from "../../src/features/attendance/domain/TodayStatusReader.ts";
import { CompanyId } from "../../src/features/shared/domain/CompanyId.ts";
import { GeoPoint } from "../../src/features/shared/domain/GeoPoint.ts";
import { WorkDate } from "../../src/features/shared/domain/WorkDate.ts";

describe("RegisterPunch", () => {
    it("sends the next punch for the signed-in employee", async () => {
        const writer = new MemoryPunches();
        const action = build(writer, openFence());

        await action.handle(new RegisterPunchData(new CompanyId(4), 10, PunchType.ClockIn, new GeoPoint(19.432608, -99.133209)));

        assert.equal(writer.commands.length, 1);
        assert.equal(writer.commands[0]?.punchType, "clock_in");
        assert.equal(writer.commands[0]?.companyId.value, 4);
        assert.equal("punchedAt" in (writer.commands[0] ?? {}), false);
    });

    it("rejects a punch that skips the cycle", async () => {
        const writer = new MemoryPunches();
        const action = build(writer, openFence());

        await assert.rejects(
            () => action.handle(new RegisterPunchData(new CompanyId(4), 10, PunchType.LunchOut)),
            InvalidPunchSequence,
        );
        assert.equal(writer.commands.length, 0);
    });

    it("rejects a location outside the branch geofence", async () => {
        const writer = new MemoryPunches();
        const action = build(writer, {
            required: true,
            center: new GeoPoint(19.432608, -99.133209),
            radiusMeters: 100,
        });

        await assert.rejects(
            () => action.handle(new RegisterPunchData(new CompanyId(4), 10, PunchType.ClockIn, new GeoPoint(19.5, -99.133209))),
            PunchOutsideGeofence,
        );
        assert.equal(writer.commands.length, 0);
    });

    it("rejects a company that is not the signed-in company", async () => {
        const action = build(new MemoryPunches(), openFence());

        await assert.rejects(
            () => action.handle(new RegisterPunchData(new CompanyId(9), 10, PunchType.ClockIn)),
            SessionCompanyMismatch,
        );
    });
});

function build(writer: PunchWriter, fence: BranchGeofence): RegisterPunch {
    const sessions = new SignedInSession();

    return new RegisterPunch(
        new RequireSession(sessions),
        new OpenDay(),
        new FixedFence(fence),
        writer,
    );
}

function openFence(): BranchGeofence {
    return { required: false, center: null, radiusMeters: null };
}

class SignedInSession implements SessionStore {
    current(): Promise<Session | null> {
        return Promise.resolve(new Session(new CompanyId(4), 7, 10, "Ana", "ana@checa.test"));
    }

    save(): Promise<void> {
        return Promise.resolve();
    }

    clear(): Promise<void> {
        return Promise.resolve();
    }
}

class OpenDay implements TodayStatusReader {
    getToday(companyId: CompanyId, employeeProfileId: number): Promise<TodayEmployeeStatus> {
        return Promise.resolve(
            new TodayEmployeeStatus(
                companyId,
                employeeProfileId,
                new WorkDate("2026-11-20"),
                [],
                AttendanceStatus.Absent,
                PunchType.ClockIn,
                0,
                0,
                0,
                0,
            ),
        );
    }
}

class FixedFence implements BranchGeofenceReader {
    constructor(private readonly fence: BranchGeofence) {}

    forEmployee(): Promise<BranchGeofence> {
        return Promise.resolve(this.fence);
    }
}

class MemoryPunches implements PunchWriter {
    readonly commands: PunchCommand[] = [];

    register(command: PunchCommand): Promise<void> {
        this.commands.push(command);

        return Promise.resolve();
    }
}
