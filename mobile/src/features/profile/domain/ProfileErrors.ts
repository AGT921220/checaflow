import { DomainError } from "../../shared/domain/DomainError.ts";

export class EmployeeNotFound extends DomainError {
    constructor() {
        super("Employee was not found in this company.");
    }
}

export class EmployeeDoesNotBelongToCompany extends DomainError {
    constructor() {
        super("Employee does not belong to this company.");
    }
}
