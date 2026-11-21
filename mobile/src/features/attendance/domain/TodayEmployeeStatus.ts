import type { CompanyId } from "../../shared/domain/CompanyId.ts";
import type { WorkDate } from "../../shared/domain/WorkDate.ts";
import type { AttendancePunch } from "./AttendancePunch.ts";
import type { AttendanceStatus } from "./AttendanceStatus.ts";
import type { PunchType } from "./PunchType.ts";

export class TodayEmployeeStatus {
    constructor(
        readonly companyId: CompanyId,
        readonly employeeProfileId: number,
        readonly workDate: WorkDate,
        readonly punches: readonly AttendancePunch[],
        readonly status: AttendanceStatus,
        readonly nextPunchType: PunchType | null,
        readonly lateMinutes: number,
        readonly lunchMinutes: number,
        readonly excessLunchMinutes: number,
        readonly workedMinutes: number,
    ) {}
}
