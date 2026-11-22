import { hideListLoader, showListLoader } from '../ui/list-loader.js';

export function readPageParams(root) {
    const params = new URLSearchParams();
    const page = root.dataset.page || '1';
    params.set('page', page);

    root.querySelectorAll('[data-list-filter]').forEach((input) => {
        if (!(input instanceof HTMLInputElement || input instanceof HTMLSelectElement)) {
            return;
        }

        if (input.value === '') {
            return;
        }

        params.set(input.name, input.value);
    });

    return params;
}

export async function fetchJson(url, params, panel = null) {
    if (panel instanceof HTMLElement) {
        showListLoader(panel);
    }

    try {
        const query = params.toString();
        const target = query === '' ? url : `${url}?${query}`;
        const response = await fetch(target, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (! response.ok) {
            throw new Error('list_fetch_failed');
        }

        return await response.json();
    } finally {
        if (panel instanceof HTMLElement) {
            hideListLoader(panel);
        }
    }
}

export function renderPager(host, meta, onPage) {
    if (!(host instanceof HTMLElement)) {
        return;
    }

    host.innerHTML = '';

    if (meta.total < 1) {
        return;
    }

    const label = document.createElement('p');
    label.className = 'text-sm text-[var(--color-ink-soft)]';
    label.textContent = `Página ${meta.page} de ${meta.last_page} · ${meta.total} registros`;
    host.appendChild(label);

    const actions = document.createElement('div');
    actions.className = 'flex gap-2';

    const prev = document.createElement('button');
    prev.type = 'button';
    prev.className = 'cf-btn cf-btn-ghost';
    prev.textContent = 'Anterior';
    prev.disabled = meta.page <= 1;
    prev.addEventListener('click', () => onPage(meta.page - 1));

    const next = document.createElement('button');
    next.type = 'button';
    next.className = 'cf-btn cf-btn-ghost';
    next.textContent = 'Siguiente';
    next.disabled = meta.page >= meta.last_page;
    next.addEventListener('click', () => onPage(meta.page + 1));

    actions.appendChild(prev);
    actions.appendChild(next);
    host.appendChild(actions);
}
