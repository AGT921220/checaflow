/**
 * Overlay loader for admin list panels during fetch.
 */
export function showListLoader(root) {
    const host = resolveHost(root);

    if (!(host instanceof HTMLElement)) {
        return;
    }

    let loader = host.querySelector('[data-list-loader]');

    if (! (loader instanceof HTMLElement)) {
        loader = document.createElement('div');
        loader.className = 'cf-list-loader';
        loader.dataset.listLoader = '';
        loader.setAttribute('role', 'status');
        loader.setAttribute('aria-live', 'polite');
        loader.innerHTML = `
            <div class="cf-list-loader-card">
                <span class="cf-list-loader-glow" aria-hidden="true"></span>
                <span class="cf-list-loader-ring" aria-hidden="true"></span>
                <p class="cf-list-loader-label">Cargando…</p>
            </div>`;
        host.appendChild(loader);
    }

    loader.hidden = false;
    host.classList.add('cf-is-loading');
}

export function hideListLoader(root) {
    const host = resolveHost(root);

    if (!(host instanceof HTMLElement)) {
        return;
    }

    host.classList.remove('cf-is-loading');
    const loader = host.querySelector('[data-list-loader]');

    if (loader instanceof HTMLElement) {
        loader.hidden = true;
    }
}

function resolveHost(root) {
    if (!(root instanceof HTMLElement)) {
        return null;
    }

    const panel = root.querySelector('.cf-panel');

    if (panel instanceof HTMLElement) {
        return panel;
    }

    return root;
}
