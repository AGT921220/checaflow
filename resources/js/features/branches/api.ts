import { definedBody, send } from '../http/client';
import { type CreateBranchInput } from './types';

export function createBranch(input: CreateBranchInput): Promise<Response> {
    return send({
        method: 'POST',
        path: '/admin/branches',
        body: definedBody([
            ['name', input.name],
            ['address', input.address],
            ['latitude', input.latitude],
            ['longitude', input.longitude],
            ['geofence_radius_meters', input.geofenceRadiusMeters],
            ['require_geofence', input.requireGeofence],
            ['is_active', input.isActive],
        ]),
    });
}
