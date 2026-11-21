import { DomainError } from "../../shared/domain/DomainError.ts";

export class SummaryBelongsToAnotherCompany extends DomainError {
    constructor() {
        super("Attendance summary belongs to another company.");
    }
}
