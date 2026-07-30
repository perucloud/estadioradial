import './admin-editor';
import './admin-media-picker';
import './admin-seo';
import './admin-tags';
import './admin-locations';
import './admin-media-library';
import './admin-delete-modal';
import './admin-category-modal';
import './admin-location-modal';
import {
    canUseGenieMorph,
    genieFallbackFrames,
    runGenieMorph,
} from './admin-genie-modal';

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
    const openDialog = (trigger = null) => {
        triggerButton = trigger;
        isClosing = false;
        dialog.classList.remove('is-closing');
        updateSummary();
        if (canUseGenieMorph(reduceMotion) && trigger) dialog.style.opacity = '0';
        dialog.showModal();
        genieAnimation?.cancel();

        if (canUseGenieMorph(reduceMotion) && trigger) {
            try {
                runGenieMorph({ dialog, trigger, direction: 'open', duration: 420 });
            } catch {
                dialog.style.removeProperty('opacity');
                genieAnimation = dialog.animate(genieFallbackFrames(dialog, trigger), {
                    duration: 400,
                    easing: 'cubic-bezier(.2, .82, .2, 1)',
                    fill: 'both',
                });
            }
        } else if (!reduceMotion.matches) {
            genieAnimation = dialog.animate(genieFallbackFrames(dialog, trigger), {
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

        if (canUseGenieMorph(reduceMotion) && triggerButton) {
            try {
                runGenieMorph({
                    dialog,
                    trigger: triggerButton,
                    direction: 'close',
                    duration: 300,
                    onFinish: () => {
                        dialog.close();
                        dialog.classList.remove('is-closing');
                        isClosing = false;
                        triggerButton?.focus();
                    },
                });
                return;
            } catch {
                dialog.style.removeProperty('opacity');
                dialog.style.removeProperty('transform');
            }
        }

        genieAnimation = dialog.animate(genieFallbackFrames(dialog, triggerButton), {
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

document.querySelectorAll('[data-homepage-settings]').forEach((form) => {
    const tabs = [...form.querySelectorAll('[data-appearance-tab]')];
    const panels = [...form.querySelectorAll('[data-appearance-panel]')];
    const presetSelect = form.querySelector('[data-hero-preset]');
    const presetStatus = form.querySelector('[data-preset-status]');
    const quantityMode = form.querySelector('[data-quantity-mode]');
    const newsLimit = form.querySelector('[data-news-limit]');
    const categoryMode = form.querySelector('[data-category-mode]');
    const categorySelector = form.querySelector('[data-category-selector]');
    const imageAnimation = form.querySelector('[data-image-animation]');
    const effectSelect = form.querySelector('[name="hero[effect]"]');
    const parallaxPointer = form.querySelector('[data-parallax-pointer]');
    const intervalInput = form.querySelector('[data-interval-seconds]');
    const presets = JSON.parse(form.dataset.heroPresets || '{}');
    let applyingPreset = false;

    const activateTab = (name, focus = false) => {
        tabs.forEach((tab) => {
            const active = tab.dataset.appearanceTab === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
            if (active && focus) tab.focus();
        });
        panels.forEach((panel) => {
            const active = panel.dataset.appearancePanel === name;
            panel.hidden = !active;
            panel.classList.toggle('is-active', active);
        });
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateTab(tab.dataset.appearanceTab));
        tab.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const target = event.key === 'Home'
                ? 0
                : event.key === 'End'
                    ? tabs.length - 1
                    : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            activateTab(tabs[target].dataset.appearanceTab, true);
        });
    });

    const setVisibility = () => {
        const showLimit = quantityMode?.value === 'specific';
        if (newsLimit) {
            newsLimit.hidden = !showLimit;
            newsLimit.querySelector('input').disabled = !showLimit;
        }

        if (categorySelector) {
            categorySelector.hidden = categoryMode?.value !== 'selected';
            categorySelector.querySelectorAll('input').forEach((input) => {
                input.disabled = categoryMode?.value !== 'selected';
            });
        }

        const usesParallax = imageAnimation?.value === 'parallax' || effectSelect?.value === 'parallax';
        if (parallaxPointer) {
            parallaxPointer.hidden = !usesParallax;
        }
    };

    const setFieldValue = (name, value) => {
        if (name === 'interval' && intervalInput) {
            intervalInput.value = Number(value) / 1000;
            return;
        }

        const fields = [...form.querySelectorAll(`[name="hero[${name}]"]`)];
        if (!fields.length) return;

        const checkbox = fields.find((field) => field.type === 'checkbox');
        if (checkbox) {
            checkbox.checked = Boolean(value);
            return;
        }

        const field = fields.find((item) => item.type !== 'hidden');
        if (!field) return;
        field.value = value;
    };

    const applyPreset = () => {
        const preset = presets[presetSelect?.value];
        if (!preset) {
            if (presetStatus) presetStatus.textContent = 'Configuración personalizada';
            return;
        }

        applyingPreset = true;
        Object.entries(preset).forEach(([name, value]) => setFieldValue(name, value));
        applyingPreset = false;
        setVisibility();
        if (presetStatus) presetStatus.textContent = `Modo ${presetSelect.selectedOptions[0].textContent.split('—')[0].trim()}`;
    };

    const markCustom = () => {
        if (applyingPreset || !presetSelect || presetSelect.value === 'custom') return;
        presetSelect.value = 'custom';
        if (presetStatus) presetStatus.textContent = 'Configuración modificada manualmente';
    };

    presetSelect?.addEventListener('change', applyPreset);
    form.querySelectorAll('[data-hero-setting]').forEach((field) => {
        field.addEventListener('change', () => {
            markCustom();
            setVisibility();
        });
        if (field.matches('input[type="number"]')) field.addEventListener('input', markCustom);
    });

    setVisibility();

    if (form.querySelector('.validation-error, .alert--danger')) {
        activateTab('hero');
    }
});
