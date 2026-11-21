import { DomainError } from "./DomainError.ts";

export class InvalidWorkDate extends DomainError {
    constructor(value: string) {
        super(`Work date [${value}] is not a calendar date.`);
    }
}

export class WorkDate {
    readonly value: string;

    constructor(value: string) {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

        if (match === null) {
            throw new InvalidWorkDate(value);
        }

        const year = Number(match[1]);
        const month = Number(match[2]);
        const day = Number(match[3]);
        const parsed = new Date(Date.UTC(year, month - 1, day));
        const sameCalendarDay =
            parsed.getUTCFullYear() === year &&
            parsed.getUTCMonth() === month - 1 &&
            parsed.getUTCDate() === day;

        if (!sameCalendarDay) {
            throw new InvalidWorkDate(value);
        }

        this.value = value;
    }

    equals(other: WorkDate): boolean {
        return this.value === other.value;
    }
}
