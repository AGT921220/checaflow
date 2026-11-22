import { fetchJson, readPageParams, renderPager } from './pager.js';
import { showListLoader } from '../ui/list-loader.js';
import { openModalById } from '../ui/modal.js';

export function mountSchedulesList() {
    const panel = document.querySelector('[data-schedules-list]');

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
            emptyHost.textContent = payload.data.length > 0 ? '' : 'Sin horarios.';
        }

        if (headerHost instanceof HTMLElement) {
            headerHost.hidden = payload.data.length < 1;
        }

        payload.data.forEach((row) => {
            const li = document.createElement('li');
            li.className = isPlatform
                ? 'cf-data-row sm:grid sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_8rem_minmax(0,1.2fr)_6rem_6.5rem] sm:items-center sm:gap-3'
                : 'cf-data-row sm:grid sm:grid-cols-[minmax(0,1.2fr)_8rem_minmax(0,1.4fr)_6rem_6.5rem] sm:items-center sm:gap-3';
            li.innerHTML = `
                <div class="min-w-0"><p class="truncate font-semibold">${esc(row.name)}</p></div>
                ${isPlatform ? `<p class="truncate text-sm text-[var(--color-ink-soft)]">${esc(row.companyName)}</p>` : ''}
                <p class="font-mono text-sm tabular-nums">${esc(row.clockIn)} – ${esc(row.clockOut)}</p>
                <p class="truncate text-sm text-[var(--color-ink-soft)]">${esc(row.workingDaysLabel)}</p>
                <div>${row.isActive ? '<span class="cf-badge cf-badge-ok">Activo</span>' : '<span class="cf-badge cf-badge-muted">Inactivo</span>'}</div>
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
    const form = document.querySelector('[data-schedule-edit-form]');

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    form.action = row.updateUrl;
    form.querySelector('[name="_form"]').value = `edit:${row.id}`;
    set(form, 'name', row.name);
    set(form, 'clock_in_time', row.clockIn);
    set(form, 'clock_out_time', row.clockOut);
    set(form, 'late_tolerance_minutes', row.lateToleranceMinutes);
    set(form, 'allowed_lunch_minutes', row.allowedLunchMinutes);
    form.querySelectorAll('[name="working_days[]"]').forEach((input) => {
        if (input instanceof HTMLInputElement) {
            input.checked = row.workingDays.includes(input.value);
        }
    });
    const active = form.querySelector('[name="is_active"][type="checkbox"]');

    if (active instanceof HTMLInputElement) {
        active.checked = Boolean(row.isActive);
    }

    const title = document.querySelector('[data-schedule-edit-title]');

    if (title instanceof HTMLElement) {
        title.textContent = row.name;
    }

    openModalById('schedule-edit');
}

function set(form, name, value) {
    const input = form.querySelector(`[name="${name}"]`);

    if (input instanceof HTMLInputElement) {
        input.value = String(value ?? '');
    }
}

function esc(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

mountSchedulesList();
