export type PunchType = 'clock_in' | 'lunch_out' | 'lunch_in' | 'clock_out';

export type RegisterPunchInput = {
    punchType: PunchType;
    latitude?: number;
    longitude?: number;
};
