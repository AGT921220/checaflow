import { RequireSession } from "../../auth/application/RequireSession.ts";
import type { AttendanceHistoryCriteria } from "../domain/AttendanceHistoryCriteria.ts";
import type { AttendanceHistoryReader } from "../domain/AttendanceHistoryReader.ts";
import type { DailyAttendanceSummary } from "../domain/DailyAttendanceSummary.ts";
import { SummaryBelongsToAnotherCompany } from "../domain/HistoryErrors.ts";

export class GetAttendanceHistory {
    constructor(
        private readonly session: RequireSession,
        private readonly history: AttendanceHistoryReader,
    ) {}

    async handle(criteria: AttendanceHistoryCriteria): Promise<readonly DailyAttendanceSummary[]> {
        const current = await this.session.handle(criteria.companyId, criteria.employeeProfileId);
        const rows = await this.history.search(criteria);

        for (const row of rows) {
            if (!row.companyId.equals(current.companyId) || row.employeeProfileId !== current.employeeProfileId) {
                throw new SummaryBelongsToAnotherCompany();
            }
        }

        return rows;
    }
}
