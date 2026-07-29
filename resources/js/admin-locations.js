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
            countryCode.required = isCountry;
            countryCode.disabled = !isCountry;
        }
    };

    type.addEventListener('change', synchronize);
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
    if (selects.length !== levelNames.length) return;

    const updateStatus = () => {
        const path = selects
            .map((select) => select.selectedOptions[0])
            .filter((option) => option?.value)
            .map((option) => option.textContent.trim());

        if (status) status.textContent = path.length > 0 ? path.join(' → ') : 'Sin ubicación';
    };

    const synchronize = () => {
        selects.slice(1).forEach((select, index) => {
            const parentValue = selects[index].value;
            const selected = select.selectedOptions[0];

            [...select.options].forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const compatible = parentValue !== '' && option.dataset.parentId === parentValue;
                option.hidden = !compatible;
                option.disabled = !compatible;
            });

            if (selected?.disabled) select.value = '';
            select.disabled = parentValue === '';
        });

        updateStatus();
    };

    selects.forEach((select, index) => {
        select.addEventListener('change', () => {
            selects.slice(index + 1).forEach((child) => {
                child.value = '';
            });
            synchronize();
        });
    });

    synchronize();
});
