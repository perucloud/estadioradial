const mediaPicker = document.querySelector('[data-media-picker]');

if (mediaPicker) {
    const grid = mediaPicker.querySelector('[data-media-picker-grid]');
    const status = mediaPicker.querySelector('[data-media-picker-status]');
    const searchForm = mediaPicker.querySelector('[data-media-picker-search]');
    const searchInput = searchForm?.querySelector('input[type="search"]');
    const applyButton = mediaPicker.querySelector('[data-media-picker-apply]');
    const moreButton = mediaPicker.querySelector('[data-media-picker-more]');
    const selectionLabel = mediaPicker.querySelector('[data-media-picker-selection]');
    const title = mediaPicker.querySelector('[data-media-picker-title]');
    const libraryUrl = mediaPicker.dataset.libraryUrl;
    const items = new Map();
    let activeMode = 'inline';
    let ownerForm;
    let selectedId;
    let currentPage = 1;
    let lastPage = 1;
    let loadingController;

    const setLoading = (loading) => {
        mediaPicker.classList.toggle('is-loading', loading);
        applyButton.disabled = loading || !selectedId;
        moreButton.disabled = loading;
    };

    const updateSelection = () => {
        const media = items.get(Number(selectedId));

        selectionLabel.textContent = media?.alt_text || (selectedId ? `Imagen #${selectedId}` : 'Ninguna imagen seleccionada');
        applyButton.disabled = mediaPicker.classList.contains('is-loading') || !media;
        applyButton.textContent = activeMode === 'featured'
            ? 'Usar como imagen destacada'
            : 'Insertar en la noticia';

        grid.querySelectorAll('[data-media-picker-item]').forEach((button) => {
            const isSelected = Number(button.dataset.mediaId) === Number(selectedId);
            button.classList.toggle('is-selected', isSelected);
            button.setAttribute('aria-pressed', String(isSelected));
        });
    };

    const createMediaButton = (media) => {
        const button = document.createElement('button');
        const image = document.createElement('img');
        const body = document.createElement('span');
        const description = document.createElement('strong');
        const filename = document.createElement('small');
        const check = document.createElement('i');

        button.type = 'button';
        button.dataset.mediaPickerItem = '';
        button.dataset.mediaId = media.id;
        button.setAttribute('aria-pressed', 'false');

        image.src = media.thumb_url;
        image.alt = '';
        image.loading = 'lazy';

        description.textContent = media.alt_text;
        filename.textContent = media.name;
        check.textContent = '✓';
        check.setAttribute('aria-hidden', 'true');

        body.append(description, filename);
        button.append(image, body, check);
        button.addEventListener('click', () => {
            selectedId = Number(media.id);
            updateSelection();
        });

        return button;
    };

    const loadMedia = async ({ page = 1, append = false } = {}) => {
        loadingController?.abort();
        loadingController = new AbortController();
        setLoading(true);

        if (!append) {
            grid.replaceChildren();
            items.clear();
            status.textContent = 'Actualizando la biblioteca…';
        } else {
            status.textContent = 'Cargando más imágenes…';
        }

        try {
            const url = new URL(libraryUrl, window.location.origin);
            url.searchParams.set('page', page);
            url.searchParams.set('per_page', '48');
            if (searchInput?.value.trim()) url.searchParams.set('q', searchInput.value.trim());

            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                signal: loadingController.signal,
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const payload = await response.json();
            currentPage = payload.meta.current_page;
            lastPage = payload.meta.last_page;

            payload.data.forEach((media) => {
                items.set(Number(media.id), media);
                grid.append(createMediaButton(media));
            });

            const visible = grid.childElementCount;
            status.textContent = payload.meta.total === 0
                ? 'No se encontraron imágenes. Puedes subirlas desde el módulo Media.'
                : `${visible} de ${payload.meta.total} imágenes disponibles`;
            moreButton.hidden = currentPage >= lastPage;
            updateSelection();
        } catch (error) {
            if (error.name === 'AbortError') return;

            status.textContent = 'No se pudo actualizar la biblioteca. Inténtalo nuevamente.';
            moreButton.hidden = true;
        } finally {
            setLoading(false);
            updateSelection();
        }
    };

    const openPicker = (button) => {
        activeMode = button.dataset.mediaPickerMode || 'inline';
        ownerForm = button.closest('form');
        selectedId = activeMode === 'featured'
            ? Number(ownerForm?.querySelector('[data-featured-media-input]')?.value) || undefined
            : undefined;
        title.textContent = activeMode === 'featured'
            ? 'Seleccionar imagen destacada'
            : 'Insertar imagen en la noticia';
        searchInput.value = '';
        mediaPicker.showModal();
        loadMedia();
    };

    const updateFeaturedPreview = (media) => {
        const input = ownerForm?.querySelector('[data-featured-media-input]');
        const preview = ownerForm?.querySelector('[data-featured-media-preview]');
        if (!input || !preview) return;

        const image = preview.querySelector('[data-featured-media-image]');
        const placeholder = preview.querySelector('[data-featured-media-placeholder]');
        const caption = preview.querySelector('[data-featured-media-caption]');

        input.value = media.id;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        image.src = media.thumb_url;
        image.alt = media.alt_text;
        image.hidden = false;
        placeholder.hidden = true;
        caption.hidden = false;
        preview.classList.add('has-image');
        preview.querySelector('[data-featured-media-name]').textContent = media.name;
        preview.querySelector('[data-featured-media-alt]').textContent = media.alt_text;
        ownerForm.querySelector('[data-featured-media-action]').textContent = 'Cambiar imagen destacada';
        ownerForm.querySelector('.featured-media-panel .field-error')?.remove();
    };

    document.querySelectorAll('[data-open-media-picker]').forEach((button) => {
        button.addEventListener('click', () => openPicker(button));
    });

    mediaPicker.querySelector('[data-media-picker-close]').addEventListener('click', () => mediaPicker.close());
    mediaPicker.querySelector('[data-media-picker-cancel]').addEventListener('click', () => mediaPicker.close());
    mediaPicker.querySelector('[data-media-picker-refresh]').addEventListener('click', () => loadMedia());

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loadMedia();
    });

    moreButton.addEventListener('click', () => {
        if (currentPage < lastPage) loadMedia({ page: currentPage + 1, append: true });
    });

    applyButton.addEventListener('click', () => {
        const media = items.get(Number(selectedId));
        if (!media || !ownerForm) return;

        if (activeMode === 'featured') updateFeaturedPreview(media);

        ownerForm.dispatchEvent(new CustomEvent('media-picker:selected', {
            bubbles: true,
            detail: { mode: activeMode, media },
        }));
        mediaPicker.close();
    });

    window.addEventListener('focus', () => {
        if (mediaPicker.open) loadMedia();
    });
}
