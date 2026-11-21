export type JsonValue = string | number | boolean | null | string[];

export type WebRequest = {
    method: 'GET' | 'POST';
    path: string;
    body?: Record<string, JsonValue>;
};

export type UnroutedRequest = {
    method: 'POST';
    body: Record<string, JsonValue>;
};

export function rejectCompanyScope(body: Record<string, JsonValue>): void {
    if (Object.hasOwn(body, 'company_id') || Object.hasOwn(body, 'companyId')) {
        throw new Error('La empresa sale de la sesión.');
    }
}

export function definedBody(entries: Array<[string, JsonValue | undefined]>): Record<string, JsonValue> {
    const body: Record<string, JsonValue> = {};

    for (const [key, value] of entries) {
        if (value === undefined) {
            continue;
        }

        body[key] = value;
    }

    rejectCompanyScope(body);

    return body;
}

function csrfToken(): string | null {
    const row = document.cookie.split('; ').find((part) => part.startsWith('XSRF-TOKEN='));

    if (row === undefined) {
        return null;
    }

    return decodeURIComponent(row.slice('XSRF-TOKEN='.length));
}

export async function send(request: WebRequest): Promise<Response> {
    if (request.body !== undefined) {
        rejectCompanyScope(request.body);
    }

    const headers = new Headers({
        Accept: 'text/html, application/json',
        'X-Requested-With': 'XMLHttpRequest',
    });
    const token = csrfToken();

    if (token !== null) {
        headers.set('X-XSRF-TOKEN', token);
    }

    if (request.body !== undefined) {
        headers.set('Content-Type', 'application/json');
    }

    return fetch(request.path, {
        method: request.method,
        credentials: 'same-origin',
        headers,
        body: request.body === undefined ? undefined : JSON.stringify(request.body),
    });
}
