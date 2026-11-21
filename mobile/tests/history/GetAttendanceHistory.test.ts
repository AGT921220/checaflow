import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { RequireSession } from "../../src/features/auth/application/RequireSession.ts";
import { Session } from "../../src/features/auth/domain/Session.ts";
import type { SessionStore } from "../../src/features/auth/domain/SessionStore.ts";
import { AttendanceStatus } from "../../src/features/attendance/domain/AttendanceStatus.ts";
import { GetAttendanceHistory } from "../../src/features/history/application/GetAttendanceHistory.ts";
import { AttendanceHistoryCriteria, InvalidHistoryRange } from "../../src/features/history/domain/AttendanceHistoryCriteria.ts";
import type { AttendanceHistoryReader } from "../../src/features/history/domain/AttendanceHistoryReader.ts";
import { DailyAttendanceSummary } from "../../src/features/history/domain/DailyAttendanceSummary.ts";
import { SummaryBelongsToAnotherCompany } from "../../src/features/history/domain/HistoryErrors.ts";
import { CompanyId } from "../../src/features/shared/domain/CompanyId.ts";
import { InvalidSortColumn } from "../../src/features/shared/domain/Sort.ts";
import { WorkDate } from "../../src/features/shared/domain/WorkDate.ts";

describe("GetAttendanceHistory", () => {
    it("returns rows that belong to the signed-in employee", async () => {
        const row = summary(new CompanyId(4), 10);
        const action = new GetAttendanceHistory(new RequireSession(new SignedInSession()), new MemoryHistory([row]));

        const rows = await action.handle(criteria(new CompanyId(4)));

        assert.equal(rows.length, 1);
        assert.equal(rows[0]?.workedMinutes, 470);
    });

    it("rejects a summary leaked from another company", async () => {
        const action = new GetAttendanceHistory(
            new RequireSession(new SignedInSession()),
            new MemoryHistory([summary(new CompanyId(9), 10)]),
        );

        await assert.rejects(() => action.handle(criteria(new CompanyId(4))), SummaryBelongsToAnotherCompany);
    });

    it("rejects an inverted date range and an unknown sort column", () => {
        assert.throws(
            () => criteria(new CompanyId(4), "2026-11-21", "2026-11-20"),
            InvalidHistoryRange,
        );
        assert.throws(
            () => new AttendanceHistoryCriteria(new CompanyId(4), 10, new WorkDate("2026-11-01"), new WorkDate("2026-11-20"), "name; drop table daily_attendance_summaries"),
            InvalidSortColumn,
        );
    });
});

function criteria(companyId: CompanyId, from = "2026-11-01", to = "2026-11-20"): AttendanceHistoryCriteria {
    return new AttendanceHistoryCriteria(companyId, 10, new WorkDate(from), new WorkDate(to));
}

function summary(companyId: CompanyId, employeeProfileId: number): DailyAttendanceSummary {
    return new DailyAttendanceSummary(
        companyId,
        employeeProfileId,
        3,
        8,
        new WorkDate("2026-11-20"),
        "2026-11-20T09:00:00Z",
        "2026-11-20T13:00:00Z",
        "2026-11-20T14:10:00Z",
        "2026-11-20T18:00:00Z",
        0,
        70,
        10,
        470,
        AttendanceStatus.OnTime,
    );
}

class SignedInSession implements SessionStore {
    current(): Promise<Session | null> {
        return Promise.resolve(new Session(new CompanyId(4), 7, 10, "Ana", "ana@checa.test"));
    }

    save(): Promise<void> {
        return Promise.resolve();
    }

    clear(): Promise<void> {
        return Promise.resolve();
    }
}

class MemoryHistory implements AttendanceHistoryReader {
    constructor(private readonly rows: readonly DailyAttendanceSummary[]) {}

    search(): Promise<readonly DailyAttendanceSummary[]> {
        return Promise.resolve(this.rows);
    }
}
