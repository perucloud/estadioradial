import {
    canUseGenieMorph,
    genieFallbackFrames,
    runGenieMorph,
} from './admin-genie-modal';

const categoryAdmin = document.querySelector('[data-category-admin]');

if (categoryAdmin) {
    const createDialog = categoryAdmin.querySelector('[data-category-create-dialog]');
    const editDialog = categoryAdmin.querySelector('[data-category-edit-dialog]');
    const editForm = categoryAdmin.querySelector('[data-category-edit-form]');
    const createTrigger = categoryAdmin.querySelector('[data-category-create-open]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const dialogStates = new WeakMap();

    const stateFor = (dialog) => {
        if (!dialogStates.has(dialog)) {
            dialogStates.set(dialog, {
                trigger: null,
                animation: null,
                closing: false,
            });
        }

        return dialogStates.get(dialog);
    };

    const openDialog = (dialog, trigger) => {
        const state = stateFor(dialog);
        if (dialog.open || state.closing) return;

        state.trigger = trigger;
        state.closing = false;
        dialog.classList.remove('is-closing');
        if (canUseGenieMorph(reduceMotion)) dialog.style.opacity = '0';
        dialog.showModal();
        state.animation?.cancel();

        if (canUseGenieMorph(reduceMotion)) {
            try {
                runGenieMorph({
                    dialog,
                    trigger,
                    direction: 'open',
                    duration: 420,
                });
            } catch {
                dialog.style.removeProperty('opacity');
                state.animation = dialog.animate(genieFallbackFrames(dialog, trigger), {
                    duration: 420,
                    easing: 'cubic-bezier(.2, .82, .2, 1)',
                    fill: 'both',
                });
            }
        }

        window.setTimeout(
            () => dialog.querySelector('input[name="name"]')?.focus(),
            reduceMotion.matches ? 30 : 280,
        );
    };

    const closeDialog = (dialog) => {
        const state = stateFor(dialog);
        if (!dialog.open || state.closing) return;

        if (reduceMotion.matches) {
            dialog.close();
            state.trigger?.focus();
            return;
        }

        state.closing = true;
        dialog.classList.add('is-closing');
        state.animation?.cancel();
        const finish = () => {
            dialog.close();
            dialog.classList.remove('is-closing');
            state.closing = false;
            state.trigger?.focus();
        };

        if (canUseGenieMorph(reduceMotion) && state.trigger) {
            try {
                runGenieMorph({
                    dialog,
                    trigger: state.trigger,
                    direction: 'close',
                    duration: 300,
                    onFinish: finish,
                });
                return;
            } catch {
                dialog.style.removeProperty('opacity');
                dialog.style.removeProperty('transform');
            }
        }

        state.animation = dialog.animate(genieFallbackFrames(dialog, state.trigger), {
            duration: 300,
            easing: 'cubic-bezier(.55, .02, .78, .35)',
            direction: 'reverse',
            fill: 'both',
        });
        state.animation.addEventListener('finish', finish, { once: true });
    };

    const bindDialog = (dialog) => {
        dialog.querySelectorAll('[data-category-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => closeDialog(dialog));
        });
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) closeDialog(dialog);
        });
        dialog.addEventListener('cancel', (event) => {
            event.preventDefault();
            closeDialog(dialog);
        });
    };

    const setValue = (form, name, value) => {
        const field = form.querySelector(`[name="${name}"]:not([type="hidden"])`);
        if (!field) return;

        if (field.type === 'checkbox') {
            field.checked = value === true || value === 1 || value === '1' || value === 'true';
            return;
        }

        field.value = value ?? '';
    };

    const populateEditForm = (payload, overrides = {}) => {
        const values = { ...payload, ...overrides };
        const editableFields = [
            'name',
            'slug',
            'parent_id',
            'color',
            'icon',
            'description',
            'relevance_weight',
            'homepage_limit',
            'homepage_layout',
            'is_active',
            'show_in_menu',
            'show_on_home',
            'seo_title',
            'seo_description',
        ];

        editForm.action = payload.update_url;
        editForm.querySelector('[data-category-context]').value = `edit:${payload.id}`;
        editableFields.forEach((name) => setValue(editForm, name, values[name]));

        const parent = editForm.querySelector('[data-category-parent]');
        parent.querySelectorAll('option').forEach((option) => {
            option.disabled = option.value === String(payload.id);
        });
        if (parent.selectedOptions[0]?.disabled) parent.value = '';

        editDialog.querySelector('#category-edit-title').textContent = `Editar: ${payload.name}`;
        editDialog.querySelector('[data-category-edit-summary]').textContent =
            `Guardando cambios en “${payload.name}”.`;
        editForm.querySelector('[name="slug"]')?.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const parsePayload = (trigger) => {
        try {
            return JSON.parse(trigger.dataset.categoryPayload || '{}');
        } catch {
            return {};
        }
    };

    const openEdit = (trigger, overrides = {}) => {
        const payload = parsePayload(trigger);
        if (!payload.id || !payload.update_url) return;

        populateEditForm(payload, overrides);
        openDialog(editDialog, trigger);
    };

    bindDialog(createDialog);
    bindDialog(editDialog);
    createTrigger.addEventListener('click', () => openDialog(createDialog, createTrigger));
    categoryAdmin.querySelectorAll('[data-category-edit]').forEach((button) => {
        button.addEventListener('click', () => openEdit(button));
    });

    const openContext = categoryAdmin.dataset.categoryOpenContext;
    if (openContext) {
        let oldValues = {};
        try {
            oldValues = JSON.parse(categoryAdmin.dataset.categoryOldValues || '{}');
        } catch {
            oldValues = {};
        }

        window.requestAnimationFrame(() => {
            if (openContext === 'create') {
                openDialog(createDialog, createTrigger);
                return;
            }

            const categoryId = openContext.startsWith('edit:') ? openContext.split(':')[1] : null;
            const trigger = categoryId
                ? categoryAdmin.querySelector(`[data-category-edit][data-category-id="${categoryId}"]`)
                : null;
            if (trigger) openEdit(trigger, oldValues);
        });
    }
}
