import { RequireSession } from "../../auth/application/RequireSession.ts";
import { NotesTooLong } from "../domain/AttendanceErrors.ts";
import { AttendanceDay } from "../domain/AttendanceDay.ts";
import type { BranchGeofenceReader } from "../domain/BranchGeofenceReader.ts";
import { GeofenceCheck } from "../domain/GeofenceCheck.ts";
import type { PunchWriter } from "../domain/PunchWriter.ts";
import type { RegisterPunchData } from "../domain/RegisterPunchData.ts";
import type { TodayStatusReader } from "../domain/TodayStatusReader.ts";

export class RegisterPunch {
    constructor(
        private readonly session: RequireSession,
        private readonly todayStatus: TodayStatusReader,
        private readonly geofences: BranchGeofenceReader,
        private readonly punches: PunchWriter,
    ) {}

    async handle(command: RegisterPunchData): Promise<void> {
        const current = await this.session.handle(command.companyId, command.employeeProfileId);
        const today = await this.todayStatus.getToday(current.companyId, current.employeeProfileId);
        const day = AttendanceDay.open(today.punches);
        const notes = normalizeNotes(command.notes);

        day.assertAccepts(command.punchType);

        const fence = await this.geofences.forEmployee(current.companyId, current.employeeProfileId);
        new GeofenceCheck(fence).match(command.location);

        await this.punches.register({
            companyId: current.companyId,
            employeeProfileId: current.employeeProfileId,
            punchType: command.punchType,
            location: command.location,
            notes,
        });
    }
}

function normalizeNotes(notes: string | null): string | null {
    if (notes === null) {
        return null;
    }

    const trimmed = notes.trim();

    if (trimmed === "") {
        return null;
    }

    if (trimmed.length > 2000) {
        throw new NotesTooLong();
    }

    return trimmed;
}
