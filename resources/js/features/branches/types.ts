export type CreateBranchInput = {
    name: string;
    address?: string;
    latitude?: number;
    longitude?: number;
    geofenceRadiusMeters?: number;
    requireGeofence: boolean;
    isActive: boolean;
};
