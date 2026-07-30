const parentTypes = {
    country: null,
    region: 'country',
    province: 'region',
    district: 'province',
};

const labels = {
    country: 'país',
    region: 'región',
    province: 'provincia',
    district: 'distrito',
};

document.querySelectorAll('[data-location-form]').forEach((form) => {
    const type = form.querySelector('[data-location-type]');
    const parent = form.querySelector('[data-location-parent]');
    const countryCode = form.querySelector('[data-country-code]');
    const help = form.querySelector('[data-location-parent-help]');
    const optionsUrl = form.dataset.locationOptionsUrl;
    let loadedParentType;
    if (!type || !parent) return;

    const synchronize = () => {
        const expected = parentTypes[type.value];
        const selected = parent.selectedOptions[0];

        [...parent.options].forEach((option) => {
            if (option.value === '') {
                option.hidden = expected !== null;
                option.disabled = expected !== null;
                return;
            }

            const compatible = option.dataset.locationOptionType === expected;
            option.hidden = !compatible;
            option.disabled = !compatible;
        });

        if (selected?.disabled) parent.value = '';
        parent.required = expected !== null;

        if (help) {
            help.textContent = expected === null
                ? 'Los países se crean en el nivel principal.'
                : `Selecciona una ${labels[expected]} como ubicación superior.`;
        }

        if (countryCode) {
            const isCountry = type.value === 'country';
            countryCode.disabled = !isCountry;
        }
    };

    const loadParents = async () => {
        const expected = parentTypes[type.value];
        if (!expected || !optionsUrl || loadedParentType === expected) return;

        const selectedValue = parent.dataset.pendingValue ?? parent.value;
        parent.disabled = true;
        parent.setAttribute('aria-busy', 'true');

        try {
            const url = new URL(optionsUrl, window.location.origin);
            url.searchParams.set('type', expected);
            url.searchParams.set('all', '1');
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('No se pudieron cargar las ubicaciones superiores.');
            const payload = await response.json();
            const root = parent.options[0];
            parent.replaceChildren(root);

            payload.data.forEach((location) => {
                if (String(location.id) === form.dataset.locationId) return;

                const option = new Option(`${location.name} · ${labels[location.type]}`, location.id);
                option.dataset.locationOptionType = location.type;
                parent.add(option);
            });
            parent.value = selectedValue;
            delete parent.dataset.pendingValue;
            loadedParentType = expected;
        } catch {
            if (help) help.textContent = 'No se pudieron cargar las ubicaciones superiores.';
        } finally {
            parent.disabled = false;
            parent.removeAttribute('aria-busy');
            synchronize();
        }
    };

    type.addEventListener('change', () => {
        loadedParentType = undefined;
        parent.value = '';
        synchronize();
        loadParents();
    });
    parent.addEventListener('focus', loadParents, { once: false });
    synchronize();
});

document.querySelectorAll('[data-sortable-locations]').forEach((body) => {
    let dragged;

    const updateOrder = () => {
        [...body.querySelectorAll('[data-location-row]')].forEach((row, index) => {
            const input = row.querySelector('.order-input');
            if (input) input.value = (index + 1) * 10;
        });
    };

    body.querySelectorAll('[data-location-row]').forEach((row) => {
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

document.querySelectorAll('[data-post-location]').forEach((panel) => {
    const levelNames = ['country', 'region', 'province', 'district'];
    const selects = levelNames
        .map((level) => panel.querySelector(`[data-post-location-level="${level}"]`))
        .filter(Boolean);
    const status = panel.querySelector('[data-post-location-status]');
    const badgeEnabled = panel.querySelector('[data-editorial-badge-enabled]');
    const badgeCustom = panel.querySelector('[data-editorial-badge-custom]');
    const badgePreview = panel.querySelector('[data-editorial-badge-preview]');
    const badgeLabel = panel.querySelector('[data-editorial-badge-label]');
    const optionsUrl = panel.dataset.locationOptionsUrl;
    let requestController;
    if (selects.length !== levelNames.length) return;

    const selectedName = (level) => {
        const select = selects[levelNames.indexOf(level)];
        const option = select?.selectedOptions[0];

        return option?.value ? option.textContent.trim() : '';
    };

    const updateBadgePreview = () => {
        if (!badgePreview || !badgeLabel) return;

        badgePreview.hidden = badgeEnabled ? !badgeEnabled.checked : false;
        const automatic = [selectedName('district'), selectedName('region')]
            .filter((name, index, names) => name && names.indexOf(name) === index)
            .join(' · ');
        badgeLabel.textContent = badgeCustom?.value.trim() || automatic || selectedName('country') || 'Sin ubicación';
    };

    const updateStatus = () => {
        const path = selects
            .map((select) => select.selectedOptions[0])
            .filter((option) => option?.value)
            .map((option) => option.textContent.trim());

        if (status) status.textContent = path.length > 0 ? path.join(' → ') : 'Sin ubicación';
        updateBadgePreview();
    };

    const synchronize = () => {
        selects.slice(1).forEach((select, index) => {
            select.disabled = selects[index].value === '';
        });
        updateStatus();
    };

    selects.forEach((select, index) => {
        select.addEventListener('change', async () => {
            selects.slice(index + 1).forEach((child) => {
                child.replaceChildren(child.options[0]);
                child.disabled = true;
            });

            const child = selects[index + 1];
            if (!child || select.value === '' || !optionsUrl) {
                synchronize();
                return;
            }

            requestController?.abort();
            requestController = new AbortController();
            child.disabled = true;
            child.setAttribute('aria-busy', 'true');

            try {
                const url = new URL(optionsUrl, window.location.origin);
                url.searchParams.set('type', levelNames[index + 1]);
                url.searchParams.set('parent_id', select.value);
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: requestController.signal,
                });

                if (!response.ok) throw new Error('No se pudieron cargar las ubicaciones.');
                const payload = await response.json();

                payload.data.forEach((location) => {
                    const option = new Option(location.name, location.id);
                    option.dataset.parentId = location.parent_id;
                    child.add(option);
                });
                child.disabled = false;
            } catch (error) {
                if (error.name !== 'AbortError' && status) {
                    status.textContent = 'No se pudieron cargar las ubicaciones';
                }
            } finally {
                child.removeAttribute('aria-busy');
            }

            synchronize();
        });
    });

    badgeEnabled?.addEventListener('change', updateBadgePreview);
    badgeCustom?.addEventListener('input', updateBadgePreview);
    synchronize();
});
