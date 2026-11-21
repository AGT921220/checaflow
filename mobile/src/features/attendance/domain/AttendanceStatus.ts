export const AttendanceStatus = {
    OnTime: "on_time",
    Late: "late",
    Incomplete: "incomplete",
    Absent: "absent",
} as const;

export type AttendanceStatus = (typeof AttendanceStatus)[keyof typeof AttendanceStatus];
