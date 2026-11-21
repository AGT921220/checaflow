import type { CompanyId } from "../../shared/domain/CompanyId.ts";
import type { GeoPoint } from "../../shared/domain/GeoPoint.ts";
import type { PunchType } from "./PunchType.ts";

export class RegisterPunchData {
    constructor(
        readonly companyId: CompanyId,
        readonly employeeProfileId: number,
        readonly punchType: PunchType,
        readonly location: GeoPoint | null = null,
        readonly notes: string | null = null,
    ) {}
}
