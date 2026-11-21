import { CompanyId } from "../../shared/domain/CompanyId.ts";
import { DomainError } from "../../shared/domain/DomainError.ts";

export class InvalidUserId extends DomainError {
    constructor() {
        super("User id must be a positive number.");
    }
}

export class InvalidEmployeeProfileId extends DomainError {
    constructor() {
        super("Employee profile id must be a positive number.");
    }
}

export class Session {
    constructor(
        readonly companyId: CompanyId,
        readonly userId: number,
        readonly employeeProfileId: number,
        readonly name: string,
        readonly email: string,
    ) {
        if (!Number.isInteger(this.userId) || this.userId < 1) {
            throw new InvalidUserId();
        }

        if (!Number.isInteger(this.employeeProfileId) || this.employeeProfileId < 1) {
            throw new InvalidEmployeeProfileId();
        }
    }
}
