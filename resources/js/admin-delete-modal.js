const bypassConfirmation = new WeakSet();
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let dialog;
let pendingForm;
let pendingSubmitter;
let opener;
let closeTimer;
let busy = false;
let movedField;
let fieldPlaceholder;

const ensureDeleteModal = () => {
    if (dialog) return dialog;

    dialog = document.createElement('dialog');
    dialog.className = 'admin-delete-modal';
    dialog.setAttribute('aria-labelledby', 'admin-delete-modal-title');
    dialog.setAttribute('aria-describedby', 'admin-delete-modal-description');
    dialog.innerHTML = `
        <section class="admin-delete-modal__surface">
            <header class="admin-delete-modal__header">
                <span class="admin-delete-modal__icon" aria-hidden="true">!</span>
                <button class="admin-delete-modal__close" type="button" data-delete-modal-close aria-label="Cerrar">×</button>
            </header>
            <div class="admin-delete-modal__content">
                <span class="eyebrow">Acción irreversible</span>
                <h2 id="admin-delete-modal-title" data-delete-modal-title>Confirmar eliminación</h2>
                <p id="admin-delete-modal-description" data-delete-modal-description></p>
                <strong class="admin-delete-modal__name" data-delete-modal-name hidden></strong>
                <div class="admin-delete-modal__options" data-delete-modal-options hidden></div>
                <p class="admin-delete-modal__warning">
                    Una vez confirmada, esta acción no se puede deshacer.
                </p>
            </div>
            <footer class="admin-delete-modal__footer">
                <button class="button button--quiet" type="button" data-delete-modal-close>Cancelar</button>
                <button class="admin-delete-modal__confirm" type="button" data-delete-modal-confirm>
                    Eliminar definitivamente
                </button>
            </footer>
        </section>
    `;
    document.body.append(dialog);

    dialog.querySelectorAll('[data-delete-modal-close]').forEach((button) => {
        button.addEventListener('click', () => closeDeleteModal());
    });
    dialog.querySelector('[data-delete-modal-confirm]').addEventListener('click', confirmDeletion);
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) closeDeleteModal();
    });
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeDeleteModal();
    });

    return dialog;
};

const restoreDeleteField = () => {
    if (!movedField || !fieldPlaceholder) return;

    movedField.querySelectorAll('[data-delete-modal-required]').forEach((field) => {
        field.required = false;
        field.setCustomValidity('');
    });
    movedField.hidden = true;
    fieldPlaceholder.replaceWith(movedField);
    movedField = null;
    fieldPlaceholder = null;
};

const resetState = () => {
    restoreDeleteField();
    pendingForm = null;
    pendingSubmitter = null;
    busy = false;
};

const closeDeleteModal = ({ restoreFocus = true } = {}) => {
    if (!dialog?.open || busy) return;

    window.clearTimeout(closeTimer);
    dialog.classList.remove('is-visible');
    const finish = () => {
        dialog.close();
        resetState();
        if (restoreFocus && opener?.isConnected) opener.focus();
        opener = null;
    };

    if (reduceMotion.matches) {
        finish();
        return;
    }

    closeTimer = window.setTimeout(finish, 180);
};

function confirmDeletion() {
    if (!pendingForm || busy) return;

    const invalidField = movedField?.querySelector('[data-delete-modal-required]:invalid');
    if (invalidField) {
        invalidField.setCustomValidity('Selecciona una opción antes de continuar.');
        invalidField.reportValidity();
        invalidField.addEventListener('change', () => invalidField.setCustomValidity(''), { once: true });
        return;
    }

    busy = true;
    const confirmButton = dialog.querySelector('[data-delete-modal-confirm]');
    confirmButton.disabled = true;
    confirmButton.textContent = 'Eliminando…';
    dialog.setAttribute('aria-busy', 'true');
    restoreDeleteField();
    bypassConfirmation.add(pendingForm);
    pendingForm.requestSubmit(pendingSubmitter || undefined);
}

export const openDeleteModal = (form, trigger = null) => {
    const modal = ensureDeleteModal();
    const name = form.dataset.confirmName?.trim();
    const confirmButton = modal.querySelector('[data-delete-modal-confirm]');
    opener = trigger || document.activeElement;
    pendingForm = form;
    pendingSubmitter = trigger?.matches?.('[type="submit"]') ? trigger : null;
    busy = false;

    modal.querySelector('[data-delete-modal-title]').textContent = form.dataset.confirmTitle || 'Confirmar eliminación';
    modal.querySelector('[data-delete-modal-description]').textContent = form.dataset.confirmDelete
        || '¿Deseas eliminar este elemento?';
    const nameElement = modal.querySelector('[data-delete-modal-name]');
    nameElement.textContent = name || '';
    nameElement.hidden = !name;
    const options = modal.querySelector('[data-delete-modal-options]');
    restoreDeleteField();
    movedField = form.querySelector('[data-delete-modal-field]');
    options.hidden = !movedField;
    options.replaceChildren();
    if (movedField) {
        fieldPlaceholder = document.createComment('modal-delete-field');
        movedField.before(fieldPlaceholder);
        movedField.hidden = false;
        movedField.querySelectorAll('[data-delete-modal-required]').forEach((field) => {
            field.required = true;
        });
        options.append(movedField);
    }
    confirmButton.textContent = form.dataset.confirmButton || 'Eliminar definitivamente';
    confirmButton.disabled = false;
    modal.removeAttribute('aria-busy');

    window.clearTimeout(closeTimer);
    modal.showModal();
    window.requestAnimationFrame(() => {
        modal.classList.add('is-visible');
        window.setTimeout(() => confirmButton.focus(), reduceMotion.matches ? 0 : 180);
    });
};

document.addEventListener('submit', (event) => {
    const form = event.target.closest?.('form[data-confirm-delete]');
    if (!form) return;

    if (bypassConfirmation.has(form)) {
        bypassConfirmation.delete(form);
        return;
    }

    event.preventDefault();
    openDeleteModal(form, event.submitter);
}, true);
