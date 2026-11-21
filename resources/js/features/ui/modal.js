/**
 * Lightweight modals: [data-modal-open="id"], [data-modal="id"], [data-modal-close].
 */
export function mountModals(root = document) {
    const openers = root.querySelectorAll('[data-modal-open]');

    openers.forEach((opener) => {
        if (!(opener instanceof HTMLElement)) {
            return;
        }

        opener.addEventListener('click', () => {
            const id = opener.dataset.modalOpen;

            if (!id) {
                return;
            }

            openModalById(id);
        });
    });

    root.querySelectorAll('[data-modal]').forEach((modal) => {
        if (!(modal instanceof HTMLElement)) {
            return;
        }

        modal.querySelectorAll('[data-modal-close]').forEach((closer) => {
            closer.addEventListener('click', () => closeModal(modal));
        });

        modal.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeModal(modal);
            }
        });
    });

    const auto = root.querySelector('[data-modal][data-modal-auto-open="1"]');

    if (auto instanceof HTMLElement) {
        const id = auto.dataset.modal;

        if (id) {
            openModalById(id);
        }
    }
}

export function openModalById(id) {
    const modal = document.querySelector(`[data-modal="${CSS.escape(id)}"]`);

    if (!(modal instanceof HTMLElement)) {
        return;
    }

    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    modal.dispatchEvent(new CustomEvent('cf:modal-open', { bubbles: true }));

    const focusable = modal.querySelector('input, select, textarea, button');

    if (focusable instanceof HTMLElement) {
        focusable.focus();
    }
}

function closeModal(modal) {
    modal.hidden = true;

    if (document.querySelector('[data-modal]:not([hidden])')) {
        return;
    }

    document.body.style.overflow = '';
}

mountModals();
