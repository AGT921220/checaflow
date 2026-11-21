export const PunchType = {
    ClockIn: "clock_in",
    LunchOut: "lunch_out",
    LunchIn: "lunch_in",
    ClockOut: "clock_out",
} as const;

export type PunchType = (typeof PunchType)[keyof typeof PunchType];

export const punchOrder: readonly PunchType[] = [
    PunchType.ClockIn,
    PunchType.LunchOut,
    PunchType.LunchIn,
    PunchType.ClockOut,
];

const nextByType: Record<PunchType, PunchType | null> = {
    clock_in: PunchType.LunchOut,
    lunch_out: PunchType.LunchIn,
    lunch_in: PunchType.ClockOut,
    clock_out: null,
};

export function nextPunchType(current: PunchType): PunchType | null {
    return nextByType[current];
}
