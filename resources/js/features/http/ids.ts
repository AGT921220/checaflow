const ORIGIN = 'http://checaflow.local';

export function positiveId(value: number): number {
    if (!Number.isInteger(value) || value < 1) {
        throw new Error('El identificador no es válido.');
    }

    return value;
}

export function pathWithId(base: string, id: number, tail?: string): string {
    const url = new URL(base, ORIGIN);
    const segment = encodeURIComponent(String(positiveId(id)));
    const head = url.pathname.replace(/\/$/, '');

    url.pathname = `${head}/${segment}`;

    if (tail !== undefined) {
        url.pathname = `${url.pathname}/${encodeURIComponent(tail)}`;
    }

    return url.pathname;
}

export function pagePath(pathname: string, query: Record<string, string | number | undefined> = {}): string {
    const url = new URL(pathname, ORIGIN);
    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(query)) {
        if (value === undefined) {
            continue;
        }

        if (key === 'company_id' || key === 'companyId') {
            throw new Error('La empresa sale de la sesión.');
        }

        params.set(key, String(value));
    }

    url.search = params.toString();

    return `${url.pathname}${url.search}`;
}
