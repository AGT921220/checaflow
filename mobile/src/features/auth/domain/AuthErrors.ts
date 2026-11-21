import { DomainError } from "../../shared/domain/DomainError.ts";

export class InvalidEmail extends DomainError {
    constructor() {
        super("Email address is not valid.");
    }
}

export class InvalidPassword extends DomainError {
    constructor() {
        super("Password is required.");
    }
}

export class InvalidCredentials extends DomainError {
    constructor() {
        super("Email or password is incorrect.");
    }
}

export class UserIsInactive extends DomainError {
    constructor() {
        super("User is inactive.");
    }
}

export class EmployeeCompanyRequired extends DomainError {
    constructor() {
        super("The mobile app only signs in employees who belong to a company.");
    }
}

export class NotAuthenticated extends DomainError {
    constructor() {
        super("Sign in before continuing.");
    }
}

export class SessionCompanyMismatch extends DomainError {
    constructor() {
        super("The signed-in user does not belong to this company.");
    }
}

export class SessionEmployeeMismatch extends DomainError {
    constructor() {
        super("The signed-in user does not match this employee profile.");
    }
}
