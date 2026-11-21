import { RequireSession } from "../../auth/application/RequireSession.ts";
import type { CompanyId } from "../../shared/domain/CompanyId.ts";
import { AttendanceBelongsToAnotherCompany } from "../domain/AttendanceErrors.ts";
import { AttendanceDay } from "../domain/AttendanceDay.ts";
import type { TodayEmployeeStatus } from "../domain/TodayEmployeeStatus.ts";
import type { TodayStatusReader } from "../domain/TodayStatusReader.ts";

export class GetTodayStatus {
    constructor(
        private readonly session: RequireSession,
        private readonly todayStatus: TodayStatusReader,
    ) {}

    async handle(companyId: CompanyId, employeeProfileId: number): Promise<TodayEmployeeStatus> {
        const current = await this.session.handle(companyId, employeeProfileId);
        const today = await this.todayStatus.getToday(current.companyId, current.employeeProfileId);

        if (!today.companyId.equals(current.companyId) || today.employeeProfileId !== current.employeeProfileId) {
            throw new AttendanceBelongsToAnotherCompany();
        }

        AttendanceDay.open(today.punches);

        return today;
    }
}
