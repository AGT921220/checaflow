export const PunchSource = {
    MobileWeb: "mobile_web",
    MobileApp: "mobile_app",
    WebAdmin: "web_admin",
    ManualAdjustment: "manual_adjustment",
} as const;

export type PunchSource = (typeof PunchSource)[keyof typeof PunchSource];
