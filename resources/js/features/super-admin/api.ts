import { type UnroutedRequest, type WebRequest, definedBody, send } from '../http/client';
import { positiveId } from '../http/ids';
import { type OpenCompanyAccountInput } from './types';

export function buildOpenCompanyAccountRequest(input: OpenCompanyAccountInput): UnroutedRequest {
    return {
        method: 'POST',
        body: definedBody([
            ['company_name', input.companyName],
            ['plan_id', positiveId(input.planId)],
            ['max_employees', input.maxEmployees],
            ['first_name', input.firstName],
            ['last_name', input.lastName],
            ['nickname', input.nickname],
            ['email', input.email ?? ''],
            ['phone', input.phone ?? ''],
            ['password', input.password],
        ]),
    };
}

export function openCompanyAccount(input: OpenCompanyAccountInput): Promise<Response> {
    const request = buildOpenCompanyAccountRequest(input);

    return send({
        method: 'POST',
        path: '/platform/companies',
        body: request.body,
    } satisfies WebRequest);
}
