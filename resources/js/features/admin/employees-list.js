import { fetchJson, readPageParams, renderPager } from './pager.js';
import { openModalById } from '../ui/modal.js';

export function mountEmployeesList(root = document) {
    const panel = root.querySelector('[data-employees-list]');

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
        const params = readPageParams(panel);
        const payload = await fetchJson(listUrl, params);
        renderRows(rowsHost, emptyHost, headerHost, payload.data, canManage, isPlatform);
        renderPager(pagerHost, payload.meta, (page) => {
            panel.dataset.page = String(page);
            load().catch(() => {});
        });
        fillCreateOptions(payload.filters);
    };

    panel.querySelectorAll('[data-list-filter]').forEach((input) => {
        input.addEventListener('change', () => {
            panel.dataset.page = '1';
            load().catch(() => {});
        });
    });

    const applyBtn = panel.querySelector('[data-list-apply]');

    if (applyBtn instanceof HTMLElement) {
        applyBtn.addEventListener('click', () => {
            panel.dataset.page = '1';
            load().catch(() => {});
        });
    }

    load().catch(() => {
        if (emptyHost instanceof HTMLElement) {
            emptyHost.hidden = false;
            emptyHost.textContent = 'No se pudo cargar el listado.';
        }
    });
}

function fillCreateOptions(filters) {
    if (! filters) {
        return;
    }

    fillSelect('branch_id', filters.branches || []);
    fillSelect('schedule_id', filters.schedules || []);
}

function fillSelect(name, options) {
    document.querySelectorAll(`select[name="${name}"]`).forEach((select) => {
        if (!(select instanceof HTMLSelectElement)) {
            return;
        }

        const current = select.value;
        const keepFirstEmpty = select.querySelector('option[value=""]') !== null;
        select.innerHTML = '';

        if (keepFirstEmpty) {
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'Todas';
            select.appendChild(empty);
        }

        options.forEach((option) => {
            const row = document.createElement('option');
            row.value = String(option.id);
            row.textContent = option.name;
            select.appendChild(row);
        });

        select.value = current;
    });
}

function renderRows(rowsHost, emptyHost, headerHost, rows, canManage, isPlatform) {
    rowsHost.innerHTML = '';

    if (emptyHost instanceof HTMLElement) {
        emptyHost.hidden = rows.length > 0;
    }

    if (headerHost instanceof HTMLElement) {
        headerHost.hidden = rows.length < 1;
    }

    rows.forEach((row) => {
        const li = document.createElement('li');
        li.className = isPlatform
            ? 'cf-data-row sm:grid sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_7rem_minmax(0,1fr)_minmax(0,1fr)_6rem_6.5rem] sm:items-center sm:gap-3'
            : 'cf-data-row sm:grid sm:grid-cols-[minmax(0,1.4fr)_7rem_minmax(0,1fr)_minmax(0,1fr)_6rem_6.5rem] sm:items-center sm:gap-3';

        const person = document.createElement('div');
        person.className = 'flex min-w-0 items-center gap-3';
        person.innerHTML = row.photoUrl
            ? `<img src="${escapeAttr(row.photoUrl)}" alt="" class="cf-avatar">`
            : `<div class="cf-avatar-fallback">${escapeHtml((row.name || '?').slice(0, 1).toUpperCase())}</div>`;
        const text = document.createElement('div');
        text.className = 'min-w-0';
        text.innerHTML = `<p class="truncate font-semibold">${escapeHtml(row.name)}</p>
            <p class="truncate text-sm text-[var(--color-ink-soft)]">${escapeHtml(row.nickname)} · ${escapeHtml(row.positionTitle || 'Sin puesto')}</p>`;
        person.appendChild(text);
        li.appendChild(person);

        if (isPlatform) {
            li.appendChild(cell(row.companyName, 'Empresa'));
        }

        li.appendChild(monoCell(row.employeeCode, 'Código'));
        li.appendChild(cell(row.branchName, 'Sucursal'));
        li.appendChild(cell(row.scheduleName, 'Horario'));

        const status = document.createElement('div');
        status.innerHTML = row.isActive
            ? '<span class="cf-badge cf-badge-ok">Activo</span>'
            : '<span class="cf-badge cf-badge-muted">Inactivo</span>';
        li.appendChild(status);

        const actions = document.createElement('div');
        actions.className = 'flex sm:justify-end';

        if (canManage) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'cf-btn cf-btn-ghost';
            button.textContent = 'Editar';
            button.addEventListener('click', () => fillEditModal(row));
            actions.appendChild(button);
        }

        li.appendChild(actions);
        rowsHost.appendChild(li);
    });
}

function fillEditModal(row) {
    const form = document.querySelector('[data-employee-edit-form]');

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    form.action = row.updateUrl;
    form.querySelector('[name="_form"]').value = `edit:${row.id}`;
    setValue(form, 'name', row.name);
    setValue(form, 'nickname', row.nickname);
    setValue(form, 'email', row.email || '');
    setValue(form, 'phone', row.phone || '');
    setValue(form, 'employee_code', row.employeeCode);
    setValue(form, 'position_title', row.positionTitle || '');
    setValue(form, 'hire_date', row.hireDate || '');
    setValue(form, 'branch_id', String(row.branchId));
    setValue(form, 'schedule_id', String(row.scheduleId));
    const active = form.querySelector('[name="is_active"][type="checkbox"]');

    if (active instanceof HTMLInputElement) {
        active.checked = Boolean(row.isActive);
    }

    const title = document.querySelector('[data-employee-edit-title]');

    if (title instanceof HTMLElement) {
        title.textContent = `${row.name} · ${row.employeeCode}`;
    }

    openModalById('employee-edit');
}

function setValue(form, name, value) {
    const input = form.querySelector(`[name="${name}"]`);

    if (input instanceof HTMLInputElement || input instanceof HTMLSelectElement) {
        input.value = value;
    }
}

function cell(value, label) {
    const p = document.createElement('p');
    p.className = 'truncate text-sm text-[var(--color-ink-soft)]';
    p.innerHTML = `<span class="sm:hidden">${escapeHtml(label)} · </span>${escapeHtml(value || '—')}`;

    return p;
}

function monoCell(value, label) {
    const p = document.createElement('p');
    p.className = 'font-mono text-sm tabular-nums text-[var(--color-ink-soft)] sm:text-[var(--color-ink)]';
    p.innerHTML = `<span class="sm:hidden font-sans text-[var(--color-ink-soft)]">${escapeHtml(label)} · </span>${escapeHtml(value || '—')}`;

    return p;
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function escapeAttr(value) {
    return escapeHtml(value).replaceAll("'", '&#39;');
}

mountEmployeesList();
