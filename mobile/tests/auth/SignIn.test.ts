import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { SignIn } from "../../src/features/auth/application/SignIn.ts";
import type { AuthenticatedUser } from "../../src/features/auth/domain/AuthenticatedUser.ts";
import type { CredentialsAuthenticator } from "../../src/features/auth/domain/CredentialsAuthenticator.ts";
import { EmployeeCompanyRequired, InvalidCredentials } from "../../src/features/auth/domain/AuthErrors.ts";
import type { Session } from "../../src/features/auth/domain/Session.ts";
import type { SessionStore } from "../../src/features/auth/domain/SessionStore.ts";
import { SignInData } from "../../src/features/auth/domain/SignInData.ts";

describe("SignIn", () => {
    it("stores a session for an employee of a company", async () => {
        const sessions = new MemorySessions();
        const action = new SignIn(new MemoryAuthenticator(employeeUser()), sessions);

        const session = await action.handle(new SignInData("Ana@Checa.test", "secret"));

        assert.equal(session.companyId.value, 4);
        assert.equal(session.employeeProfileId, 10);
        assert.equal((await sessions.current())?.email, "ana@checa.test");
    });

    it("rejects a platform operator without a company", async () => {
        const action = new SignIn(
            new MemoryAuthenticator({ ...employeeUser(), companyId: null, employeeProfileId: null }),
            new MemorySessions(),
        );

        await assert.rejects(
            () => action.handle(new SignInData("root@checa.test", "secret")),
            EmployeeCompanyRequired,
        );
    });

    it("rejects unknown credentials", async () => {
        const action = new SignIn(new MemoryAuthenticator(null), new MemorySessions());

        await assert.rejects(() => action.handle(new SignInData("ana@checa.test", "nope")), InvalidCredentials);
    });
});

function employeeUser(): AuthenticatedUser {
    return {
        companyId: 4,
        userId: 7,
        employeeProfileId: 10,
        name: "Ana",
        email: "ana@checa.test",
        isActive: true,
    };
}

class MemoryAuthenticator implements CredentialsAuthenticator {
    constructor(private readonly user: AuthenticatedUser | null) {}

    authenticate(): Promise<AuthenticatedUser | null> {
        return Promise.resolve(this.user);
    }
}

class MemorySessions implements SessionStore {
    private session: Session | null = null;

    current(): Promise<Session | null> {
        return Promise.resolve(this.session);
    }

    save(session: Session): Promise<void> {
        this.session = session;

        return Promise.resolve();
    }

    clear(): Promise<void> {
        this.session = null;

        return Promise.resolve();
    }
}
