import type { CompanyId } from "../../shared/domain/CompanyId.ts";

export class EmployeeProfile {
    constructor(
        readonly companyId: CompanyId,
        readonly employeeProfileId: number,
        readonly userId: number,
        readonly branchId: number,
        readonly scheduleId: number,
        readonly employeeCode: string,
        readonly positionTitle: string | null,
        readonly hireDate: string | null,
        readonly isActive: boolean,
        readonly name: string,
        readonly email: string,
    ) {}
}
