import { DomainError } from "./DomainError.ts";

export class InvalidCompanyId extends DomainError {
    constructor() {
        super("Company id must be a positive number.");
    }
}

export class CompanyId {
    readonly value: number;

    constructor(value: number) {
        if (!Number.isInteger(value) || value < 1) {
            throw new InvalidCompanyId();
        }

        this.value = value;
    }

    equals(other: CompanyId): boolean {
        return this.value === other.value;
    }
}
