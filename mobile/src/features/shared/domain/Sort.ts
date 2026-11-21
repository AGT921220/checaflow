import { DomainError } from "./DomainError.ts";

export class InvalidSortColumn extends DomainError {
    constructor(column: string) {
        super(`Column [${column}] cannot be used for sorting.`);
    }
}

export class InvalidSortDirection extends DomainError {
    constructor(direction: string) {
        super(`Sort direction [${direction}] is not allowed.`);
    }
}

export class Sort {
    readonly column: string;

    readonly direction: "asc" | "desc";

    constructor(column: string, direction: string, allowedColumns: readonly string[]) {
        if (!allowedColumns.includes(column)) {
            throw new InvalidSortColumn(column);
        }

        if (direction !== "asc" && direction !== "desc") {
            throw new InvalidSortDirection(direction);
        }

        this.column = column;
        this.direction = direction;
    }
}
