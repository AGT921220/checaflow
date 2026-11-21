import type { AttendanceHistoryCriteria } from "./AttendanceHistoryCriteria.ts";
import type { DailyAttendanceSummary } from "./DailyAttendanceSummary.ts";

export interface AttendanceHistoryReader {
    search(criteria: AttendanceHistoryCriteria): Promise<readonly DailyAttendanceSummary[]>;
}
