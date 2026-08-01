import {
    canUseGenieMorph,
    genieFallbackFrames,
    runGenieMorph,
} from './admin-genie-modal';

const mediaPicker = document.querySelector('[data-media-picker]');

if (mediaPicker) {
    const grid = mediaPicker.querySelector('[data-media-picker-grid]');
    const status = mediaPicker.querySelector('[data-media-picker-status]');
    const searchForm = mediaPicker.querySelector('[data-media-picker-search]');
    const searchInput = searchForm?.querySelector('input[type="search"]');
    const applyButton = mediaPicker.querySelector('[data-media-picker-apply]');
    const moreButton = mediaPicker.querySelector('[data-media-picker-more]');
    const selectionLabel = mediaPicker.querySelector('[data-media-picker-selection]');
    const selectedUrl = mediaPicker.querySelector('[data-media-picker-url]');
    const copyButton = mediaPicker.querySelector('[data-media-picker-copy]');
    const title = mediaPicker.querySelector('[data-media-picker-title]');
    const uploadForm = mediaPicker.querySelector('[data-media-picker-upload]');
    const uploadToggle = mediaPicker.querySelector('[data-media-picker-upload-toggle]');
    const uploadStatus = mediaPicker.querySelector('[data-media-picker-upload-status]');
    const uploadFileInput = uploadForm.querySelector('input[type="file"]');
    const libraryUrl = mediaPicker.dataset.libraryUrl;
    const uploadUrl = mediaPicker.dataset.uploadUrl;
    const items = new Map();
    let activeMode = 'inline';
    let ownerForm;
    let selectedId;
    let currentPage = 1;
    let lastPage = 1;
    let loadingController;
    let uploadInProgress = false;
    let modalTrigger;
    let modalAnimation;
    let isClosing = false;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const automaticUploadMessage = 'La carga comenzará automáticamente al seleccionar el archivo.';

    const setLoading = (loading) => {
        mediaPicker.classList.toggle('is-loading', loading);
        applyButton.disabled = loading || !selectedId;
        moreButton.disabled = loading;
    };

    const updateSelection = () => {
        const media = items.get(Number(selectedId));

        selectionLabel.textContent = media?.alt_text || (selectedId ? `Imagen #${selectedId}` : 'Ninguna imagen seleccionada');
        selectedUrl.value = media?.article_url || '';
        copyButton.disabled = !media?.article_url;
        applyButton.disabled = mediaPicker.classList.contains('is-loading') || !media;
        applyButton.textContent = activeMode === 'featured'
            ? 'Usar como imagen destacada'
            : activeMode === 'logo'
                ? 'Usar como logo'
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
        modalTrigger = button;
        activeMode = button.dataset.mediaPickerMode || 'inline';
        ownerForm = button.closest('form');
        selectedId = activeMode === 'featured'
            ? Number(ownerForm?.querySelector('[data-featured-media-input]')?.value) || undefined
            : activeMode === 'logo'
                ? Number(ownerForm?.querySelector('[data-logo-media-input]')?.value) || undefined
                : undefined;
        title.textContent = activeMode === 'featured'
            ? 'Seleccionar imagen destacada'
            : activeMode === 'logo'
                ? 'Seleccionar logo del portal'
                : 'Insertar imagen en la noticia';
        uploadToggle.textContent = activeMode === 'logo'
            ? 'Subir logo desde ordenador'
            : '+ Añadir nueva imagen';
        searchInput.value = '';
        uploadForm.hidden = true;
        uploadForm.reset();
        uploadStatus.textContent = automaticUploadMessage;
        isClosing = false;
        mediaPicker.classList.remove('is-closing');
        if (canUseGenieMorph(reduceMotion)) mediaPicker.style.opacity = '0';
        mediaPicker.showModal();
        modalAnimation?.cancel();

        if (canUseGenieMorph(reduceMotion)) {
            try {
                runGenieMorph({ dialog: mediaPicker, trigger: button, direction: 'open', duration: 300 });
            } catch {
                mediaPicker.style.removeProperty('opacity');
                modalAnimation = mediaPicker.animate(genieFallbackFrames(mediaPicker, button), {
                    duration: 300,
                    easing: 'cubic-bezier(.2, .82, .2, 1)',
                    fill: 'both',
                });
            }
        }
        loadMedia();
    };

    const closePicker = () => {
        if (!mediaPicker.open || isClosing) return;

        if (reduceMotion.matches || !modalTrigger) {
            mediaPicker.close();
            modalTrigger?.focus();
            return;
        }

        isClosing = true;
        mediaPicker.classList.add('is-closing');
        modalAnimation?.cancel();
        const finish = () => {
            mediaPicker.close();
            mediaPicker.classList.remove('is-closing');
            isClosing = false;
            modalTrigger?.focus();
        };

        if (canUseGenieMorph(reduceMotion)) {
            try {
                runGenieMorph({ dialog: mediaPicker, trigger: modalTrigger, direction: 'close', duration: 300, onFinish: finish });
                return;
            } catch {
                mediaPicker.style.removeProperty('opacity');
                mediaPicker.style.removeProperty('transform');
            }
        }

        modalAnimation = mediaPicker.animate(genieFallbackFrames(mediaPicker, modalTrigger), {
            duration: 300,
            easing: 'cubic-bezier(.55, .02, .78, .35)',
            direction: 'reverse',
            fill: 'both',
        });
        modalAnimation.addEventListener('finish', finish, { once: true });
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

    const updateLogoPreview = (media) => {
        const input = ownerForm?.querySelector('[data-logo-media-input]');
        const preview = ownerForm?.querySelector('[data-logo-media-preview]');
        if (!input || !preview) return;

        input.value = media.id;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        const image = preview.querySelector('[data-logo-media-image]');
        const placeholder = preview.querySelector('[data-logo-media-placeholder]');
        image.src = media.thumb_url;
        image.alt = media.alt_text || media.name;
        image.hidden = false;
        placeholder.hidden = true;
        preview.querySelector('[data-logo-media-name]').textContent = media.name;
        preview.querySelector('[data-logo-media-alt]').textContent = media.alt_text || 'Logo del portal';
        preview.querySelector('[data-remove-logo]').hidden = false;
        preview.querySelector('.settings-logo-picker__preview')?.classList.add('has-image');
    };

    document.querySelectorAll('[data-open-media-picker]').forEach((button) => {
        button.addEventListener('click', () => openPicker(button));
    });

    mediaPicker.querySelector('[data-media-picker-close]').addEventListener('click', closePicker);
    mediaPicker.querySelector('[data-media-picker-cancel]').addEventListener('click', closePicker);
    mediaPicker.querySelector('[data-media-picker-refresh]').addEventListener('click', () => loadMedia());
    mediaPicker.querySelector('[data-media-picker-upload-close]').addEventListener('click', () => {
        uploadForm.hidden = true;
        uploadStatus.textContent = automaticUploadMessage;
    });

    uploadToggle.addEventListener('click', () => {
        uploadForm.hidden = !uploadForm.hidden;
        uploadStatus.textContent = automaticUploadMessage;

        if (!uploadForm.hidden) {
            uploadFileInput.focus();
        }
    });

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loadMedia();
    });

    moreButton.addEventListener('click', () => {
        if (currentPage < lastPage) loadMedia({ page: currentPage + 1, append: true });
    });

    const uploadSelectedMedia = async () => {
        if (uploadInProgress || !uploadFileInput.files.length) return;

        uploadInProgress = true;
        const formData = new FormData(uploadForm);
        uploadForm.classList.add('is-uploading');
        uploadFileInput.disabled = true;
        uploadStatus.textContent = 'Subiendo y procesando la imagen…';

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });
            const payload = await response.json();

            if (!response.ok) {
                const validationMessage = Object.values(payload.errors || {}).flat()[0];
                throw new Error(validationMessage || payload.message || 'No se pudo subir la imagen.');
            }

            const uploadedMedia = payload.data?.[0];
            if (!uploadedMedia) throw new Error('La imagen se guardó, pero no pudo seleccionarse.');

            selectedId = Number(uploadedMedia.id);
            searchInput.value = '';
            uploadForm.reset();
            uploadForm.hidden = true;
            await loadMedia();
            status.textContent = 'Imagen añadida correctamente y lista para utilizar.';
        } catch (error) {
            uploadStatus.textContent = error.message || 'No se pudo subir la imagen.';
            uploadFileInput.value = '';
        } finally {
            uploadInProgress = false;
            uploadFileInput.disabled = false;
            uploadForm.classList.remove('is-uploading');
        }
    };

    uploadForm.addEventListener('submit', (event) => {
        event.preventDefault();
        uploadSelectedMedia();
    });

    uploadFileInput.addEventListener('change', () => {
        uploadSelectedMedia();
    });

    copyButton.addEventListener('click', async () => {
        if (!selectedUrl.value) return;

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(selectedUrl.value);
            } else {
                selectedUrl.select();
                document.execCommand('copy');
                selectedUrl.setSelectionRange(0, 0);
            }

            copyButton.textContent = 'URL copiada';
            window.setTimeout(() => {
                copyButton.textContent = 'Copiar URL';
            }, 1600);
        } catch {
            selectedUrl.focus();
            selectedUrl.select();
        }
    });

    applyButton.addEventListener('click', () => {
        const media = items.get(Number(selectedId));
        if (!media || !ownerForm) return;

        if (activeMode === 'featured') updateFeaturedPreview(media);
        if (activeMode === 'logo') updateLogoPreview(media);

        ownerForm.dispatchEvent(new CustomEvent('media-picker:selected', {
            bubbles: true,
            detail: { mode: activeMode, media },
        }));
        closePicker();
    });

    document.querySelector('[data-remove-logo]')?.addEventListener('click', (event) => {
        const form = event.currentTarget.closest('form');
        const input = form?.querySelector('[data-logo-media-input]');
        const preview = form?.querySelector('[data-logo-media-preview]');
        if (!input || !preview) return;
        input.value = '';
        preview.querySelector('[data-logo-media-image]').hidden = true;
        preview.querySelector('[data-logo-media-placeholder]').hidden = false;
        preview.querySelector('[data-logo-media-name]').textContent = 'Logo predeterminado';
        preview.querySelector('[data-logo-media-alt]').textContent = 'Se utilizará la identidad tipográfica del portal.';
        preview.querySelector('.settings-logo-picker__preview')?.classList.remove('has-image');
        event.currentTarget.hidden = true;
    });

    mediaPicker.addEventListener('cancel', (event) => {
        event.preventDefault();
        closePicker();
    });

    window.addEventListener('focus', () => {
        if (mediaPicker.open) loadMedia();
    });
}
