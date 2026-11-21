export interface AuthenticatedUser {
    readonly companyId: number | null;
    readonly userId: number;
    readonly employeeProfileId: number | null;
    readonly name: string;
    readonly email: string;
    readonly isActive: boolean;
}
