import { type JsonValue, definedBody, send } from '../http/client';
import { type RegisterPunchInput } from './types';

function rejectServerFields(body: Record<string, JsonValue>): void {
    if (Object.hasOwn(body, 'punched_at') || Object.hasOwn(body, 'punchedAt') || Object.hasOwn(body, 'branch_id')) {
        throw new Error('La hora y la sucursal las define el servidor.');
    }
}

export function registerPunch(input: RegisterPunchInput): Promise<Response> {
    const body = definedBody([
        ['punch_type', input.punchType],
        ['latitude', input.latitude],
        ['longitude', input.longitude],
    ]);

    rejectServerFields(body);

    return send({
        method: 'POST',
        path: '/attendance/punches',
        body,
    });
}
