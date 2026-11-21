import { CompanyId } from "../../shared/domain/CompanyId.ts";
import type { CredentialsAuthenticator } from "../domain/CredentialsAuthenticator.ts";
import {
    EmployeeCompanyRequired,
    InvalidCredentials,
    InvalidEmail,
    InvalidPassword,
    UserIsInactive,
} from "../domain/AuthErrors.ts";
import { Session } from "../domain/Session.ts";
import type { SessionStore } from "../domain/SessionStore.ts";
import type { SignInData } from "../domain/SignInData.ts";

export class SignIn {
    constructor(
        private readonly authenticator: CredentialsAuthenticator,
        private readonly sessions: SessionStore,
    ) {}

    async handle(command: SignInData): Promise<Session> {
        const email = normalizeEmail(command.email);
        assertPassword(command.password);

        const user = await this.authenticator.authenticate(email, command.password);

        if (user === null) {
            throw new InvalidCredentials();
        }

        if (!user.isActive) {
            throw new UserIsInactive();
        }

        if (user.companyId === null || user.employeeProfileId === null) {
            throw new EmployeeCompanyRequired();
        }

        const session = new Session(
            new CompanyId(user.companyId),
            user.userId,
            user.employeeProfileId,
            user.name,
            user.email,
        );

        await this.sessions.save(session);

        return session;
    }
}

function normalizeEmail(email: string): string {
    const normalized = email.trim().toLowerCase();

    if (normalized.length > 255 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized)) {
        throw new InvalidEmail();
    }

    return normalized;
}

function assertPassword(password: string): void {
    if (password.trim() === "") {
        throw new InvalidPassword();
    }
}
