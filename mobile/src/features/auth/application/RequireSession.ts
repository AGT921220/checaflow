import { CompanyId } from "../../shared/domain/CompanyId.ts";
import { NotAuthenticated, SessionCompanyMismatch, SessionEmployeeMismatch } from "../domain/AuthErrors.ts";
import { InvalidEmployeeProfileId, Session } from "../domain/Session.ts";
import type { SessionStore } from "../domain/SessionStore.ts";

export class RequireSession {
    constructor(private readonly sessions: SessionStore) {}

    async handle(companyId: CompanyId, employeeProfileId: number): Promise<Session> {
        if (!Number.isInteger(employeeProfileId) || employeeProfileId < 1) {
            throw new InvalidEmployeeProfileId();
        }

        const session = await this.sessions.current();

        if (session === null) {
            throw new NotAuthenticated();
        }

        if (!session.companyId.equals(companyId)) {
            throw new SessionCompanyMismatch();
        }

        if (session.employeeProfileId !== employeeProfileId) {
            throw new SessionEmployeeMismatch();
        }

        return session;
    }
}
