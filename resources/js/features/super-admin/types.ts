export type PlanCode = 'micro' | 'small_business' | 'enterprise';

export type OpenCompanyAccountInput = {
    companyName: string;
    planId: number;
    maxEmployees: number;
    firstName: string;
    lastName: string;
    nickname: string;
    email?: string | null;
    phone?: string | null;
    password: string;
};

export type PlatformDashboardTotals = {
    companyCount: number;
    activeEmployees: number;
    punchCount: number;
};

export type PlatformCompanyRow = {
    name: string;
    plan: string;
    employees: number;
    maxEmployees: number;
    punches: number;
    lastPunch: string | null;
};
