import { DomainError } from "../../shared/domain/DomainError.ts";
import type { CompanyId } from "../../shared/domain/CompanyId.ts";
import { Sort } from "../../shared/domain/Sort.ts";
import type { WorkDate } from "../../shared/domain/WorkDate.ts";

export class InvalidHistoryRange extends DomainError {
    constructor() {
        super("History start date must be on or before the end date.");
    }
}

const historyColumns = ["work_date", "status", "late_minutes", "worked_minutes"] as const;

export class AttendanceHistoryCriteria {
    readonly sort: Sort;

    constructor(
        readonly companyId: CompanyId,
        readonly employeeProfileId: number,
        readonly from: WorkDate,
        readonly to: WorkDate,
        sortColumn = "work_date",
        sortDirection = "desc",
    ) {
        if (this.from.value > this.to.value) {
            throw new InvalidHistoryRange();
        }

        this.sort = new Sort(sortColumn, sortDirection, historyColumns);
    }
}
