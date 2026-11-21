export type OpenEmployeeAccountInput = {
    name: string;
    nickname: string;
    email?: string | null;
    phone?: string | null;
    password: string;
    passwordConfirmation: string;
    employeeCode: string;
    positionTitle?: string;
    hireDate?: string;
    branchId: number;
    scheduleId: number;
};

export type AssignEmployeeScheduleInput = {
    employeeProfileId: number;
    scheduleId: number;
};
