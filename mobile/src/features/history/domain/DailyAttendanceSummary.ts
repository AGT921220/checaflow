import type { CompanyId } from "../../shared/domain/CompanyId.ts";
import type { WorkDate } from "../../shared/domain/WorkDate.ts";
import type { AttendanceStatus } from "../../attendance/domain/AttendanceStatus.ts";

export class DailyAttendanceSummary {
    constructor(
        readonly companyId: CompanyId,
        readonly employeeProfileId: number,
        readonly branchId: number,
        readonly scheduleId: number,
        readonly workDate: WorkDate,
        readonly clockInAt: string | null,
        readonly lunchOutAt: string | null,
        readonly lunchInAt: string | null,
        readonly clockOutAt: string | null,
        readonly lateMinutes: number,
        readonly lunchMinutes: number,
        readonly excessLunchMinutes: number,
        readonly workedMinutes: number,
        readonly status: AttendanceStatus,
    ) {}
}
