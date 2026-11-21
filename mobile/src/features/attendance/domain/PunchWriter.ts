import type { CompanyId } from "../../shared/domain/CompanyId.ts";
import type { GeoPoint } from "../../shared/domain/GeoPoint.ts";
import type { PunchType } from "./PunchType.ts";

export interface PunchCommand {
    readonly companyId: CompanyId;
    readonly employeeProfileId: number;
    readonly punchType: PunchType;
    readonly location: GeoPoint | null;
    readonly notes: string | null;
}

export interface PunchWriter {
    register(command: PunchCommand): Promise<void>;
}
