import './admin-editor';

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
            alt.required = true;
            alt.placeholder = `Descripción accesible de la imagen ${index + 1}`;
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
