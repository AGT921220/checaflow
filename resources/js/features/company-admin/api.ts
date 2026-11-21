import { type UnroutedRequest, definedBody, send } from '../http/client';
import { pagePath, positiveId } from '../http/ids';
import { type CreateCompanyUserInput, type DailyBoardQuery } from './types';

const GRANTED: Record<CreateCompanyUserInput['role'], true> = {
    hr: true,
    employee: true,
};

export function buildCreateCompanyUserRequest(input: CreateCompanyUserInput): UnroutedRequest {
    if (!Object.hasOwn(GRANTED, input.role)) {
        throw new Error('Ese rol no puede crearse desde la empresa.');
    }

    return {
        method: 'POST',
        body: definedBody([
            ['name', input.name],
            ['email', input.email],
            ['role', input.role],
            ['is_active', input.isActive],
        ]),
    };
}

export function loadDailyBoard(query: DailyBoardQuery = {}): Promise<Response> {
    const branchId = query.branchId === undefined ? undefined : positiveId(query.branchId);

    return send({
        method: 'GET',
        path: pagePath('/admin/attendance', {
            work_date: query.workDate,
            branch_id: branchId,
        }),
    });
}
