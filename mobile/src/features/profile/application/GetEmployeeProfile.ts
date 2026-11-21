import { RequireSession } from "../../auth/application/RequireSession.ts";
import type { CompanyId } from "../../shared/domain/CompanyId.ts";
import type { EmployeeProfile } from "../domain/EmployeeProfile.ts";
import type { EmployeeProfileReader } from "../domain/EmployeeProfileReader.ts";
import { EmployeeDoesNotBelongToCompany, EmployeeNotFound } from "../domain/ProfileErrors.ts";

export class GetEmployeeProfile {
    constructor(
        private readonly session: RequireSession,
        private readonly profiles: EmployeeProfileReader,
    ) {}

    async handle(companyId: CompanyId, employeeProfileId: number): Promise<EmployeeProfile> {
        const current = await this.session.handle(companyId, employeeProfileId);
        const profile = await this.profiles.find(current.companyId, current.employeeProfileId);

        if (profile === null) {
            throw new EmployeeNotFound();
        }

        if (!profile.companyId.equals(current.companyId) || profile.employeeProfileId !== current.employeeProfileId) {
            throw new EmployeeDoesNotBelongToCompany();
        }

        return profile;
    }
}
