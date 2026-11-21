import { definedBody, send } from '../http/client';
import { type SignInInput } from './types';

export function signIn(input: SignInInput): Promise<Response> {
    return send({
        method: 'POST',
        path: '/login',
        body: definedBody([
            ['nickname', input.nickname],
            ['password', input.password],
            ['remember', input.remember],
        ]),
    });
}

export function signOut(): Promise<Response> {
    return send({
        method: 'POST',
        path: '/logout',
    });
}
