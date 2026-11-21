export type Weekday = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday';

export type CreateScheduleInput = {
    name: string;
    clockInTime: string;
    clockOutTime: string;
    lateToleranceMinutes: number;
    allowedLunchMinutes: number;
    workingDays: Weekday[];
    isActive: boolean;
};
