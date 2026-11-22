import { fetchJson, readPageParams, renderPager } from './pager.js';
import { hideListLoader, showListLoader } from '../ui/list-loader.js';

export function mountReportsList() {
    const panel = document.querySelector('[data-reports-list]');

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

        if (payload.filters?.fromDate) {
            const from = panel.querySelector('[name="from_date"]');
            const to = panel.querySelector('[name="to_date"]');

            if (from instanceof HTMLInputElement && from.value === '') {
                from.value = payload.filters.fromDate;
            }

            if (to instanceof HTMLInputElement && to.value === '') {
                to.value = payload.filters.toDate;
            }
        }

        fillBranches(payload.filters?.branches || []);

        if (emptyHost instanceof HTMLElement) {
            emptyHost.hidden = payload.data.length > 0;
            emptyHost.textContent = payload.message || 'Sin datos.';
        }

        if (tableWrap instanceof HTMLElement) {
            tableWrap.hidden = payload.data.length < 1;
        }

        payload.data.forEach((row) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><div class="font-semibold">${esc(row.name)}</div><div class="text-[var(--color-ink-soft)]">${esc(row.code)}</div></td>
                <td>${esc(row.presentDays)}</td>
                <td>${esc(row.lateDays)}</td>
                <td>${esc(row.absentDays)}</td>
                <td>${esc(row.workedMinutes)}</td>
                <td>${esc(row.lateMinutes)}</td>`;
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
            emptyHost.textContent = 'No se pudo cargar el reporte.';
        }
    });
}

function fillBranches(branches) {
    const select = document.querySelector('[data-branch-filter]');

    if (!(select instanceof HTMLSelectElement)) {
        return;
    }

    const current = select.value;
    select.innerHTML = '<option value="">Todas</option>';
    branches.forEach((branch) => {
        const option = document.createElement('option');
        option.value = String(branch.id);
        option.textContent = branch.name;
        select.appendChild(option);
    });
    select.value = current;
}

function esc(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

mountReportsList();
