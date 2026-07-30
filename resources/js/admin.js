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
    let liquidAnimationFrame;
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
    const ensureLiquidLayer = () => {
        let layer = document.querySelector('[data-schedule-genie-layer]');
        if (layer) return layer;

        layer = document.createElement('div');
        layer.className = 'schedule-genie-layer';
        layer.setAttribute('popover', 'manual');
        layer.setAttribute('data-schedule-genie-layer', '');
        layer.setAttribute('aria-hidden', 'true');
        layer.innerHTML = `
            <svg viewBox="0 0 ${window.innerWidth} ${window.innerHeight}" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="schedule-genie-gradient" x1="0" y1="1" x2="0" y2="0">
                        <stop offset="0%" stop-color="#5b21b6" />
                        <stop offset="52%" stop-color="#7c3aed" />
                        <stop offset="100%" stop-color="#4f46e5" />
                    </linearGradient>
                    <filter id="schedule-genie-glow" x="-20%" y="-20%" width="140%" height="140%">
                        <feDropShadow dx="0" dy="10" stdDeviation="12" flood-color="#4c1d95" flood-opacity=".3" />
                    </filter>
                </defs>
                <path class="schedule-genie-layer__sheet" data-genie-sheet />
                <path class="schedule-genie-layer__shine" data-genie-shine />
            </svg>
        `;
        document.body.append(layer);

        return layer;
    };
    const lerp = (start, end, progress) => start + ((end - start) * progress);
    const point = (x, y) => `${x.toFixed(2)} ${y.toFixed(2)}`;
    const liquidPath = (buttonRect, dialogRect, progress) => {
        const topProgress = 1 - ((1 - progress) ** 3);
        const bottomProgress = progress ** 2;
        const topLeft = {
            x: lerp(buttonRect.left, dialogRect.left, topProgress),
            y: lerp(buttonRect.top, dialogRect.top, topProgress),
        };
        const topRight = {
            x: lerp(buttonRect.right, dialogRect.right, topProgress),
            y: lerp(buttonRect.top, dialogRect.top, topProgress),
        };
        const bottomLeft = {
            x: lerp(buttonRect.left, dialogRect.left, bottomProgress),
            y: lerp(buttonRect.bottom, dialogRect.bottom, bottomProgress),
        };
        const bottomRight = {
            x: lerp(buttonRect.right, dialogRect.right, bottomProgress),
            y: lerp(buttonRect.bottom, dialogRect.bottom, bottomProgress),
        };
        const rightControlOne = {
            x: lerp(topRight.x, bottomRight.x, .12),
            y: lerp(topRight.y, bottomRight.y, .44),
        };
        const rightControlTwo = {
            x: lerp(topRight.x, bottomRight.x, .9),
            y: lerp(topRight.y, bottomRight.y, .56),
        };
        const leftControlOne = {
            x: lerp(bottomLeft.x, topLeft.x, .12),
            y: lerp(bottomLeft.y, topLeft.y, .44),
        };
        const leftControlTwo = {
            x: lerp(bottomLeft.x, topLeft.x, .9),
            y: lerp(bottomLeft.y, topLeft.y, .56),
        };

        return [
            `M ${point(topLeft.x, topLeft.y)}`,
            `L ${point(topRight.x, topRight.y)}`,
            `C ${point(rightControlOne.x, rightControlOne.y)}, ${point(rightControlTwo.x, rightControlTwo.y)}, ${point(bottomRight.x, bottomRight.y)}`,
            `L ${point(bottomLeft.x, bottomLeft.y)}`,
            `C ${point(leftControlOne.x, leftControlOne.y)}, ${point(leftControlTwo.x, leftControlTwo.y)}, ${point(topLeft.x, topLeft.y)}`,
            'Z',
        ].join(' ');
    };
    const canUseLiquidGenie = () => (
        !reduceMotion.matches
        && typeof HTMLElement.prototype.showPopover === 'function'
        && typeof window.requestAnimationFrame === 'function'
    );
    const runLiquidGenie = (trigger, direction, onFinish) => {
        const layer = ensureLiquidLayer();
        const svg = layer.querySelector('svg');
        const sheet = layer.querySelector('[data-genie-sheet]');
        const shine = layer.querySelector('[data-genie-shine]');
        const triggerRect = trigger?.getBoundingClientRect() ?? dialog.getBoundingClientRect();
        const dialogRect = dialog.getBoundingClientRect();
        const duration = direction === 'open' ? 420 : 300;
        const startedAt = performance.now();

        svg.setAttribute('viewBox', `0 0 ${window.innerWidth} ${window.innerHeight}`);
        if (!layer.matches(':popover-open')) layer.showPopover();
        window.cancelAnimationFrame(liquidAnimationFrame);

        const draw = (timestamp) => {
            const elapsed = Math.min(1, (timestamp - startedAt) / duration);
            const eased = direction === 'open'
                ? 1 - ((1 - elapsed) ** 3)
                : elapsed * elapsed * (3 - (2 * elapsed));
            const progress = direction === 'open' ? eased : 1 - eased;
            const path = liquidPath(triggerRect, dialogRect, progress);
            const reveal = direction === 'open'
                ? Math.max(0, (elapsed - .54) / .46)
                : Math.max(0, 1 - (elapsed / .32));
            const sheetOpacity = direction === 'open'
                ? Math.min(1, elapsed * 4) * (1 - Math.max(0, (elapsed - .7) / .3))
                : Math.min(1, elapsed * 4);

            sheet.setAttribute('d', path);
            shine.setAttribute('d', path);
            layer.style.opacity = String(sheetOpacity);
            dialog.style.opacity = String(reveal);
            dialog.style.transform = `translate(-50%, -50%) scale(${(.97 + (reveal * .03)).toFixed(3)})`;

            if (elapsed < 1) {
                liquidAnimationFrame = window.requestAnimationFrame(draw);
                return;
            }

            layer.hidePopover();
            layer.style.removeProperty('opacity');
            dialog.style.removeProperty('opacity');
            dialog.style.removeProperty('transform');
            onFinish?.();
        };

        liquidAnimationFrame = window.requestAnimationFrame(draw);
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
        if (canUseLiquidGenie() && trigger) dialog.style.opacity = '0';
        dialog.showModal();
        genieAnimation?.cancel();

        if (canUseLiquidGenie() && trigger) {
            try {
                runLiquidGenie(trigger, 'open');
            } catch {
                dialog.style.removeProperty('opacity');
                genieAnimation = dialog.animate(genieFrames(trigger), {
                    duration: 400,
                    easing: 'cubic-bezier(.2, .82, .2, 1)',
                    fill: 'both',
                });
            }
        } else if (!reduceMotion.matches) {
            genieAnimation = dialog.animate(genieFrames(trigger), {
                duration: 400,
                easing: 'cubic-bezier(.2, .82, .2, 1)',
                fill: 'both',
            });
        }

        window.setTimeout(() => dateInput.focus(), reduceMotion.matches ? 50 : 280);
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

        if (canUseLiquidGenie() && triggerButton) {
            try {
                runLiquidGenie(triggerButton, 'close', () => {
                    dialog.close();
                    dialog.classList.remove('is-closing');
                    isClosing = false;
                    triggerButton?.focus();
                });
                return;
            } catch {
                dialog.style.removeProperty('opacity');
                dialog.style.removeProperty('transform');
            }
        }

        genieAnimation = dialog.animate(genieFrames(triggerButton), {
            duration: 280,
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
