import './admin-editor';
import './admin-media-picker';
import './admin-seo';
import './admin-tags';
import './admin-locations';

document.querySelectorAll('[data-auto-filter]').forEach((form) => {
    form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => {
            if (form.dataset.submitting === 'true') return;

            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
            form.requestSubmit();
        });
    });
});

const adminNavGroups = [...document.querySelectorAll('[data-admin-nav-group]')];
const desktopFlyoutQuery = window.matchMedia('(min-width: 961px)');
const hoverFlyoutQuery = window.matchMedia('(min-width: 961px) and (hover: hover) and (pointer: fine)');

const positionAdminFlyout = (group) => {
    group.classList.remove('opens-upward');

    if (!group.open || !desktopFlyoutQuery.matches) return;

    const flyout = group.querySelector('.admin-nav-flyout');
    if (!flyout) return;

    const viewportMargin = 14;
    const flyoutRect = flyout.getBoundingClientRect();

    if (flyoutRect.bottom > window.innerHeight - viewportMargin) {
        group.classList.add('opens-upward');
    }
};

adminNavGroups.forEach((group) => {
    const openGroup = () => {
        if (!hoverFlyoutQuery.matches) return;

        window.clearTimeout(group.closeTimer);
        group.open = true;
    };

    const scheduleClose = () => {
        if (!hoverFlyoutQuery.matches) return;

        window.clearTimeout(group.closeTimer);
        group.closeTimer = window.setTimeout(() => {
            if (group.matches(':hover') || group.contains(document.activeElement)) return;
            group.removeAttribute('open');
        }, 180);
    };

    group.addEventListener('pointerenter', openGroup);
    group.addEventListener('pointerleave', scheduleClose);
    group.addEventListener('focusin', openGroup);
    group.addEventListener('focusout', scheduleClose);

    group.addEventListener('toggle', () => {
        if (!group.open) return;

        adminNavGroups
            .filter((candidate) => candidate !== group)
            .forEach((candidate) => candidate.removeAttribute('open'));

        window.requestAnimationFrame(() => positionAdminFlyout(group));
    });
});

window.addEventListener('resize', () => {
    adminNavGroups.forEach(positionAdminFlyout);
});

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-admin-nav-group]')) return;
    adminNavGroups.forEach((group) => group.removeAttribute('open'));
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    adminNavGroups.forEach((group) => group.removeAttribute('open'));
});

document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (window.confirm(form.dataset.confirmDelete)) return;
        event.preventDefault();
    });
});

document.querySelectorAll('[data-slug-source]').forEach((title) => {
    const form = title.closest('form');
    const slug = form?.querySelector('[data-slug-target]');
    if (!slug) return;

    let manuallyEdited = slug.value !== '';
    slug.addEventListener('input', () => {
        manuallyEdited = slug.value !== '';
    });
    title.addEventListener('input', () => {
        if (manuallyEdited) return;

        slug.value = title.value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
    });
});

document.querySelectorAll('[data-publication-datetime]').forEach((input) => {
    const localDateTime = () => {
        const now = new Date();
        const pad = (value) => String(value).padStart(2, '0');

        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    };
    const syncCurrentTime = () => {
        input.max = localDateTime();
        if (input.dataset.autoDatetime === 'true') input.value = localDateTime();
    };
    const timer = window.setInterval(syncCurrentTime, 30_000);

    syncCurrentTime();
    input.addEventListener('input', () => {
        input.dataset.autoDatetime = 'false';
        window.clearInterval(timer);
    }, { once: true });
});

document.querySelectorAll('[data-schedule-modal]').forEach((dialog) => {
    const dateInput = dialog.querySelector('[data-schedule-date]');
    const timeInput = dialog.querySelector('[data-schedule-time]');
    const hiddenInput = document.querySelector('[data-scheduled-for]');
    const summary = dialog.querySelector('[data-schedule-summary] strong');
    const error = dialog.querySelector('[data-schedule-error]');
    const confirmButton = dialog.querySelector('[data-confirm-schedule]');
    const openButtons = document.querySelectorAll('[data-open-schedule-modal]');
    const closeButtons = dialog.querySelectorAll('[data-close-schedule-modal]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let triggerButton;
    let genieAnimation;
    let isClosing = false;

    if (!dateInput || !timeInput || !hiddenInput || !summary || !error || !confirmButton) return;

    const pad = (value) => String(value).padStart(2, '0');
    const inputDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const inputTime = (date) => `${pad(date.getHours())}:${pad(date.getMinutes())}`;
    const selectedDateTime = () => {
        if (!dateInput.value || !timeInput.value) return null;

        const selected = new Date(`${dateInput.value}T${timeInput.value}:00`);
        return Number.isNaN(selected.getTime()) ? null : selected;
    };
    const showError = (message = '') => {
        error.textContent = message;
        error.hidden = message === '';
        dateInput.setAttribute('aria-invalid', message === '' ? 'false' : 'true');
        timeInput.setAttribute('aria-invalid', message === '' ? 'false' : 'true');
    };
    const updateSummary = () => {
        const selected = selectedDateTime();
        dateInput.min = inputDate(new Date());

        if (!selected) {
            summary.textContent = 'Selecciona una fecha y una hora';
            return;
        }

        summary.textContent = new Intl.DateTimeFormat('es-PE', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(selected);
        showError();
    };
    const genieFrames = (trigger) => {
        const dialogRect = dialog.getBoundingClientRect();
        const triggerRect = trigger?.getBoundingClientRect();
        const originX = triggerRect
            ? triggerRect.left + (triggerRect.width / 2) - (dialogRect.left + (dialogRect.width / 2))
            : 0;
        const originY = triggerRect
            ? triggerRect.top + (triggerRect.height / 2) - (dialogRect.top + (dialogRect.height / 2))
            : 18;
        const scaleX = triggerRect
            ? Math.max(.2, Math.min(.55, triggerRect.width / dialogRect.width))
            : .9;
        const scaleY = triggerRect
            ? Math.max(.08, Math.min(.22, triggerRect.height / dialogRect.height))
            : .9;

        return [
            {
                opacity: .08,
                clipPath: 'inset(34% 0 34% 0 round 18px)',
                transform: `translate(-50%, -50%) translate(${originX}px, ${originY}px) scale(${scaleX}, ${scaleY})`,
                offset: 0,
            },
            {
                opacity: .72,
                clipPath: 'inset(9% 0 9% 0 round 18px)',
                transform: `translate(-50%, -50%) translate(${originX * .28}px, ${originY * .24}px) scale(.74, 1.08)`,
                offset: .48,
            },
            {
                opacity: 1,
                clipPath: 'inset(0 0 0 0 round 18px)',
                transform: 'translate(-50%, -50%) scale(1.035, .97)',
                offset: .76,
            },
            {
                opacity: 1,
                clipPath: 'inset(0 0 0 0 round 18px)',
                transform: 'translate(-50%, -50%) scale(1)',
                offset: 1,
            },
        ];
    };
    const openDialog = (trigger = null) => {
        triggerButton = trigger;
        isClosing = false;
        dialog.classList.remove('is-closing');
        updateSummary();
        dialog.showModal();
        genieAnimation?.cancel();

        if (!reduceMotion.matches) {
            genieAnimation = dialog.animate(genieFrames(trigger), {
                duration: 560,
                easing: 'cubic-bezier(.2, .82, .2, 1)',
                fill: 'both',
            });
        }

        window.setTimeout(() => dateInput.focus(), reduceMotion.matches ? 50 : 430);
    };
    const closeDialog = () => {
        if (!dialog.open || isClosing) return;

        if (reduceMotion.matches) {
            dialog.close();
            triggerButton?.focus();
            return;
        }

        isClosing = true;
        dialog.classList.add('is-closing');
        genieAnimation?.cancel();
        genieAnimation = dialog.animate(genieFrames(triggerButton), {
            duration: 400,
            easing: 'cubic-bezier(.55, .02, .78, .35)',
            direction: 'reverse',
            fill: 'both',
        });
        genieAnimation.addEventListener('finish', () => {
            dialog.close();
            dialog.classList.remove('is-closing');
            isClosing = false;
            triggerButton?.focus();
        }, { once: true });
    };

    openButtons.forEach((button) => button.addEventListener('click', () => openDialog(button)));
    closeButtons.forEach((button) => button.addEventListener('click', closeDialog));
    dateInput.addEventListener('input', updateSummary);
    timeInput.addEventListener('input', updateSummary);

    dialog.querySelectorAll('[data-schedule-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            const selected = new Date();
            selected.setDate(selected.getDate() + Number(button.dataset.schedulePreset));
            selected.setSeconds(0, 0);
            dateInput.value = inputDate(selected);
            if (!timeInput.value) timeInput.value = inputTime(selected);
            updateSummary();
        });
    });

    confirmButton.addEventListener('click', (event) => {
        const selected = selectedDateTime();
        if (!selected || selected.getTime() <= Date.now()) {
            event.preventDefault();
            showError('Selecciona una fecha y hora posteriores al momento actual.');
            return;
        }

        hiddenInput.value = `${dateInput.value}T${timeInput.value}`;
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) closeDialog();
    });
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeDialog();
    });

    if (dialog.dataset.openOnError === 'true') openDialog(null);
    updateSummary();
});

document.querySelectorAll('[data-media-upload]').forEach((form) => {
    const input = form.querySelector('input[type="file"]');
    const list = form.querySelector('[data-upload-list]');
    if (!input || !list) return;

    input.addEventListener('change', () => {
        list.replaceChildren();

        [...input.files].forEach((file, index) => {
            const row = document.createElement('label');
            row.className = 'upload-alt-row';
            row.textContent = file.name;

            const alt = document.createElement('input');
            alt.type = 'text';
            alt.name = 'alt_texts[]';
            alt.maxLength = 255;
            alt.placeholder = `Descripción opcional de la imagen ${index + 1}`;
            row.append(alt);
            list.append(row);
        });
    });
});

document.querySelectorAll('[data-sortable-categories]').forEach((body) => {
    let dragged;

    const updateOrder = () => {
        [...body.querySelectorAll('[data-category-row]')].forEach((row, index) => {
            const input = row.querySelector('.order-input');
            if (input) input.value = (index + 1) * 10;
        });
    };

    body.querySelectorAll('[data-category-row]').forEach((row) => {
        row.addEventListener('dragstart', () => {
            dragged = row;
            row.classList.add('is-dragging');
        });
        row.addEventListener('dragend', () => {
            row.classList.remove('is-dragging');
            dragged = undefined;
            updateOrder();
        });
        row.addEventListener('dragover', (event) => {
            event.preventDefault();
            if (!dragged || dragged === row) return;

            const bounds = row.getBoundingClientRect();
            const insertAfter = event.clientY > bounds.top + (bounds.height / 2);
            body.insertBefore(dragged, insertAfter ? row.nextSibling : row);
        });
    });
});
