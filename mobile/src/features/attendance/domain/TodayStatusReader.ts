import type { CompanyId } from "../../shared/domain/CompanyId.ts";
import type { TodayEmployeeStatus } from "./TodayEmployeeStatus.ts";

export interface TodayStatusReader {
    getToday(companyId: CompanyId, employeeProfileId: number): Promise<TodayEmployeeStatus>;
}
