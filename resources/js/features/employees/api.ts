import { definedBody, send } from '../http/client';
import { pathWithId, positiveId } from '../http/ids';
import { type AssignEmployeeScheduleInput, type OpenEmployeeAccountInput } from './types';

export function openEmployeeAccount(input: OpenEmployeeAccountInput): Promise<Response> {
    return send({
        method: 'POST',
        path: '/admin/employees',
        body: definedBody([
            ['name', input.name],
            ['nickname', input.nickname],
            ['email', input.email ?? ''],
            ['phone', input.phone ?? ''],
            ['password', input.password],
            ['password_confirmation', input.passwordConfirmation],
            ['employee_code', input.employeeCode],
            ['position_title', input.positionTitle],
            ['hire_date', input.hireDate],
            ['branch_id', positiveId(input.branchId)],
            ['schedule_id', positiveId(input.scheduleId)],
        ]),
    });
}

export function assignEmployeeSchedule(input: AssignEmployeeScheduleInput): Promise<Response> {
    return send({
        method: 'POST',
        path: pathWithId('/admin/employees', input.employeeProfileId, 'schedule'),
        body: definedBody([['schedule_id', positiveId(input.scheduleId)]]),
    });
}
