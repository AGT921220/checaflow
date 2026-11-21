export type CompanyUserRole = 'hr' | 'employee';

export type CreateCompanyUserInput = {
    name: string;
    email: string;
    role: CompanyUserRole;
    isActive: boolean;
};

export type DailyBoardQuery = {
    workDate?: string;
    branchId?: number;
};
