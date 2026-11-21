import type { CompanyId } from "../../shared/domain/CompanyId.ts";
import type { EmployeeProfile } from "./EmployeeProfile.ts";

export interface EmployeeProfileReader {
    find(companyId: CompanyId, employeeProfileId: number): Promise<EmployeeProfile | null>;
}
