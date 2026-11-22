import { fetchJson, readPageParams, renderPager } from './pager.js';
import { showListLoader } from '../ui/list-loader.js';
import { openModalById } from '../ui/modal.js';

export function mountBranchesList() {
    const panel = document.querySelector('[data-branches-list]');

    if (!(panel instanceof HTMLElement)) {
        return;
    }

    const listUrl = panel.dataset.listUrl;
    const canManage = panel.dataset.canManage === '1';
    const isPlatform = panel.dataset.isPlatform === '1';
    const rowsHost = panel.querySelector('[data-list-rows]');
    const emptyHost = panel.querySelector('[data-list-empty]');
    const pagerHost = panel.querySelector('[data-list-pager]');
    const headerHost = panel.querySelector('[data-list-header]');

    if (! listUrl || !(rowsHost instanceof HTMLElement)) {
        return;
    }

    const load = async () => {
        const payload = await fetchJson(listUrl, readPageParams(panel), panel);
        rowsHost.innerHTML = '';

        if (emptyHost instanceof HTMLElement) {
            emptyHost.hidden = payload.data.length > 0;
            emptyHost.textContent = payload.data.length > 0 ? '' : 'Sin sucursales.';
        }

        if (headerHost instanceof HTMLElement) {
            headerHost.hidden = payload.data.length < 1;
        }

        payload.data.forEach((row) => {
            const li = document.createElement('li');
            li.className = isPlatform
                ? 'cf-data-row sm:grid sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1.2fr)_7rem_7rem_6.5rem] sm:items-center sm:gap-3'
                : 'cf-data-row sm:grid sm:grid-cols-[minmax(0,1.6fr)_minmax(0,1.2fr)_7rem_7rem_6.5rem] sm:items-center sm:gap-3';
            li.innerHTML = `
                <div class="min-w-0"><p class="truncate font-semibold">${esc(row.name)}</p></div>
                ${isPlatform ? `<p class="truncate text-sm text-[var(--color-ink-soft)]">${esc(row.companyName)}</p>` : ''}
                <p class="truncate text-sm text-[var(--color-ink-soft)]">${esc(row.address || '—')}</p>
                <p class="text-sm text-[var(--color-ink-soft)]">${row.requireGeofence ? `${esc(row.geofenceRadiusMeters)} m` : 'No'}</p>
                <div>${row.isActive ? '<span class="cf-badge cf-badge-ok">Activa</span>' : '<span class="cf-badge cf-badge-muted">Inactiva</span>'}</div>
                <div class="flex sm:justify-end" data-actions></div>`;

            if (canManage && row.updateUrl) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'cf-btn cf-btn-ghost';
                button.textContent = 'Editar';
                button.addEventListener('click', () => fillEdit(row));
                li.querySelector('[data-actions]')?.appendChild(button);
            }

            rowsHost.appendChild(li);
        });

        renderPager(pagerHost, payload.meta, (page) => {
            panel.dataset.page = String(page);
            load().catch(() => {});
        });
    };

    panel.querySelectorAll('[data-list-filter]').forEach((input) => {
        input.addEventListener('change', () => {
            panel.dataset.page = '1';
            load().catch(() => {});
        });
    });

    showListLoader(panel);
    load().catch(() => {
        if (emptyHost instanceof HTMLElement) {
            emptyHost.textContent = 'No se pudo cargar el listado.';
        }
    });
}

function fillEdit(row) {
    const form = document.querySelector('[data-branch-edit-form]');

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    form.action = row.updateUrl;
    form.querySelector('[name="_form"]').value = `edit:${row.id}`;
    set(form, 'name', row.name);
    set(form, 'address', row.address || '');
    set(form, 'latitude', row.latitude ?? '');
    set(form, 'longitude', row.longitude ?? '');
    set(form, 'geofence_radius_meters', row.geofenceRadiusMeters ?? '');
    const require = form.querySelector('[name="require_geofence"][type="checkbox"]');
    const active = form.querySelector('[name="is_active"][type="checkbox"]');

    if (require instanceof HTMLInputElement) {
        require.checked = Boolean(row.requireGeofence);
    }

    if (active instanceof HTMLInputElement) {
        active.checked = Boolean(row.isActive);
    }

    const title = document.querySelector('[data-branch-edit-title]');

    if (title instanceof HTMLElement) {
        title.textContent = row.name;
    }

    openModalById('branch-edit');
}

function set(form, name, value) {
    const input = form.querySelector(`[name="${name}"]`);

    if (input instanceof HTMLInputElement) {
        input.value = value === null || value === undefined ? '' : String(value);
    }
}

function esc(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

mountBranchesList();
