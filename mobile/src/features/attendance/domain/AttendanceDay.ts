import { AttendanceDayAlreadyClosed, DuplicatePunch, InconsistentAttendanceDay, InvalidPunchSequence } from "./AttendanceErrors.ts";
import type { AttendancePunch } from "./AttendancePunch.ts";
import { PunchType, nextPunchType, punchOrder } from "./PunchType.ts";

export class AttendanceDay {
    private constructor(private readonly punches: readonly AttendancePunch[]) {}

    static open(punches: readonly AttendancePunch[]): AttendanceDay {
        const present = new Set<PunchType>();

        for (const punch of punches) {
            if (present.has(punch.punchType)) {
                throw new DuplicatePunch(punch.punchType);
            }

            present.add(punch.punchType);
        }

        let missing = false;

        for (const type of punchOrder) {
            if (present.has(type)) {
                if (missing) {
                    throw new InconsistentAttendanceDay();
                }

                continue;
            }

            missing = true;
        }

        return new AttendanceDay(punches);
    }

    nextAllowed(): PunchType | null {
        let last: PunchType | null = null;

        for (const type of punchOrder) {
            if (!this.punches.some((punch) => punch.punchType === type)) {
                continue;
            }

            last = type;
        }

        if (last === null) {
            return PunchType.ClockIn;
        }

        return nextPunchType(last);
    }

    assertAccepts(type: PunchType): void {
        const next = this.nextAllowed();

        if (next === null) {
            throw new AttendanceDayAlreadyClosed();
        }

        if (this.punches.some((punch) => punch.punchType === type)) {
            throw new DuplicatePunch(type);
        }

        if (next !== type) {
            throw new InvalidPunchSequence(next, type);
        }
    }
}
