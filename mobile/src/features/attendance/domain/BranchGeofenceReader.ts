import type { CompanyId } from "../../shared/domain/CompanyId.ts";
import type { BranchGeofence } from "./GeofenceCheck.ts";

export interface BranchGeofenceReader {
    forEmployee(companyId: CompanyId, employeeProfileId: number): Promise<BranchGeofence>;
}
