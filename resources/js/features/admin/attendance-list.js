import { fetchJson, readPageParams, renderPager } from './pager.js';
import { hideListLoader, showListLoader } from '../ui/list-loader.js';

export function mountAttendanceList() {
    const panel = document.querySelector('[data-attendance-list]');

    if (!(panel instanceof HTMLElement)) {
        return;
    }

    const listUrl = panel.dataset.listUrl;
    const rowsHost = panel.querySelector('[data-list-rows]');
    const emptyHost = panel.querySelector('[data-list-empty]');
    const tableWrap = panel.querySelector('[data-table-wrap]');
    const pagerHost = panel.querySelector('[data-list-pager]');
    const isPlatform = panel.dataset.isPlatform === '1';

    if (! listUrl || !(rowsHost instanceof HTMLElement)) {
        return;
    }

    const load = async () => {
        if (isPlatform) {
            const company = panel.querySelector('[name="company_id"]');

            if (company instanceof HTMLSelectElement && company.value === '') {
                rowsHost.innerHTML = '';
                hideListLoader(panel);

                if (tableWrap instanceof HTMLElement) {
                    tableWrap.hidden = true;
                }

                if (emptyHost instanceof HTMLElement) {
                    emptyHost.hidden = false;
                    emptyHost.textContent = 'Selecciona una empresa.';
                }

                return;
            }
        }

        const payload = await fetchJson(listUrl, readPageParams(panel), panel);
        rowsHost.innerHTML = '';
        setStat('present', payload.stats?.present);
        setStat('late', payload.stats?.late);
        setStat('lunch', payload.stats?.atLunch);
        setStat('incomplete', payload.stats?.incomplete);

        if (payload.filters?.workDate) {
            const dateInput = panel.querySelector('[name="work_date"]');

            if (dateInput instanceof HTMLInputElement && dateInput.value === '') {
                dateInput.value = payload.filters.workDate;
            }
        }

        fillBranches(payload.filters?.branches || []);

        if (emptyHost instanceof HTMLElement) {
            emptyHost.hidden = payload.data.length > 0;
            emptyHost.textContent = payload.message || 'Sin registros.';
        }

        if (tableWrap instanceof HTMLElement) {
            tableWrap.hidden = payload.data.length < 1;
        }

        payload.data.forEach((row) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><div class="font-semibold">${esc(row.name)}</div><div class="text-[var(--color-ink-soft)]">${esc(row.code)}</div></td>
                <td>${esc(row.status)}</td>
                <td class="font-mono tabular-nums">${esc(row.clockIn || '—')}</td>
                <td class="font-mono tabular-nums">${esc(row.lunchOut || '—')}</td>
                <td class="font-mono tabular-nums">${esc(row.lunchIn || '—')}</td>
                <td class="font-mono tabular-nums">${esc(row.clockOut || '—')}</td>
                <td>${esc(row.lateMinutes)}</td>
                <td>${esc(row.workedMinutes)}</td>`;
            rowsHost.appendChild(tr);
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

    panel.querySelector('[data-list-apply]')?.addEventListener('click', () => {
        panel.dataset.page = '1';
        load().catch(() => {});
    });

    showListLoader(panel);
    load().catch(() => {
        if (emptyHost instanceof HTMLElement) {
            emptyHost.textContent = 'No se pudo cargar el tablero.';
        }
    });
}

function fillBranches(branches) {
    const select = document.querySelector('[data-branch-filter]');

    if (!(select instanceof HTMLSelectElement)) {
        return;
    }

    const current = select.value;
    select.innerHTML = '<option value="">Todas las sucursales</option>';
    branches.forEach((branch) => {
        const option = document.createElement('option');
        option.value = String(branch.id);
        option.textContent = branch.name;
        select.appendChild(option);
    });
    select.value = current;
}

function setStat(name, value) {
    const node = document.querySelector(`[data-stat-${name}]`);

    if (node instanceof HTMLElement) {
        node.textContent = String(value ?? 0);
    }
}

function esc(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

mountAttendanceList();
