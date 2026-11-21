import { definedBody, send } from '../http/client';
import { type CreateScheduleInput } from './types';

export function createSchedule(input: CreateScheduleInput): Promise<Response> {
    return send({
        method: 'POST',
        path: '/admin/schedules',
        body: definedBody([
            ['name', input.name],
            ['clock_in_time', input.clockInTime],
            ['clock_out_time', input.clockOutTime],
            ['late_tolerance_minutes', input.lateToleranceMinutes],
            ['allowed_lunch_minutes', input.allowedLunchMinutes],
            ['working_days', input.workingDays],
            ['is_active', input.isActive],
        ]),
    });
}
