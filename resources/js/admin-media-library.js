import {
    canUseGenieMorph,
    genieFallbackFrames,
    runGenieMorph,
} from './admin-genie-modal';

const library = document.querySelector('[data-media-library]');

if (library) {
    const uploadForm = library.querySelector('[data-media-library-upload]');
    const fileInput = library.querySelector('[data-media-file-input]');
    const dropzone = library.querySelector('[data-media-dropzone]');
    const uploadStatus = library.querySelector('[data-media-upload-status]');
    const uploadProgress = library.querySelector('[data-media-upload-progress]');
    const grid = library.querySelector('[data-media-library-grid]');
    const total = library.querySelector('[data-media-total]');
    const dialog = library.querySelector('[data-media-metadata-dialog]');
    const metadataForm = library.querySelector('[data-media-metadata-form]');
    const metadataImage = library.querySelector('[data-media-metadata-image]');
    const metadataName = library.querySelector('[data-media-metadata-name]');
    const metadataError = library.querySelector('[data-media-metadata-error]');
    const saveMetadata = library.querySelector('[data-save-media-metadata]');
    const toast = library.querySelector('[data-media-toast]');
    const csrfToken = library.dataset.csrfToken;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let uploadInProgress = false;
    let modalTrigger;
    let modalAnimation;
    let isClosing = false;
    let toastTimer;
    let dragDepth = 0;

    const metadataFields = {
        alt: library.querySelector('[data-media-metadata-alt]'),
        caption: library.querySelector('[data-media-metadata-caption]'),
        credit: library.querySelector('[data-media-metadata-credit]'),
        license: library.querySelector('[data-media-metadata-license]'),
    };

    const messageFromPayload = (payload, fallback) => {
        const validation = Object.values(payload?.errors ?? {}).flat().filter(Boolean);
        return validation[0] || payload?.message || fallback;
    };

    const showToast = (message, tone = 'success') => {
        window.clearTimeout(toastTimer);
        toast.textContent = message;
        toast.dataset.tone = tone;
        toast.hidden = false;
        window.requestAnimationFrame(() => toast.classList.add('is-visible'));
        toastTimer = window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => {
                toast.hidden = true;
            }, 180);
        }, 2800);
    };

    const formatSize = (bytes) => `${Math.max(1, Math.round(Number(bytes) / 1024)).toLocaleString('es-PE')} KB`;

    const createMediaCard = (media) => {
        const card = document.createElement('article');
        card.className = 'media-admin-card';
        card.dataset.mediaCard = media.id;
        card.innerHTML = `
            <div class="media-admin-card__visual">
                <img loading="lazy">
            </div>
            <div class="media-admin-card__body">
                <strong></strong>
                <small></small>
                <div class="media-admin-card__actions">
                    <button class="media-card-action media-card-action--edit" type="button" data-media-edit>
                        <span aria-hidden="true">✎</span> Editar
                    </button>
                    <button class="media-card-action media-card-action--copy" type="button" data-media-copy>
                        <span aria-hidden="true">⧉</span> Copiar link
                    </button>
                </div>
                <form method="post">
                    <input type="hidden" name="_token">
                    <input type="hidden" name="_method" value="DELETE">
                    <button class="danger-link" type="submit">Eliminar imagen</button>
                </form>
            </div>
        `;

        const image = card.querySelector('img');
        const name = card.querySelector('strong');
        const details = card.querySelector('small');
        const edit = card.querySelector('[data-media-edit]');
        const copy = card.querySelector('[data-media-copy]');
        const deleteForm = card.querySelector('form');
        const deleteButton = card.querySelector('.danger-link');

        image.src = media.thumb_url;
        image.alt = media.alt_text || '';
        name.textContent = media.name;
        name.title = media.name;
        details.textContent = `${media.width} × ${media.height} · ${formatSize(media.size)}`;
        Object.assign(edit.dataset, {
            updateUrl: media.update_url,
            mediaId: media.id,
            mediaName: media.name,
            mediaThumb: media.thumb_url,
            mediaAlt: media.alt_text || '',
            mediaCaption: media.caption || '',
            mediaCredit: media.credit || '',
            mediaLicense: media.license || '',
        });
        copy.dataset.mediaUrl = media.absolute_article_url;
        deleteForm.action = media.destroy_url;
        deleteForm.querySelector('[name="_token"]').value = csrfToken;
        deleteForm.addEventListener('submit', (event) => {
            if (window.confirm('¿Retirar esta imagen?')) return;
            event.preventDefault();
        });

        if (media.is_in_use) {
            const usage = document.createElement('span');
            usage.className = 'media-usage-badge';
            usage.textContent = 'En uso';
            card.querySelector('.media-admin-card__visual').append(usage);
            deleteButton.disabled = true;
            deleteButton.textContent = 'Protegida porque está en uso';
        }

        return card;
    };

    const updateTotal = (increment) => {
        const current = Number.parseInt(total.textContent, 10) || 0;
        total.textContent = `${current + increment} archivo(s)`;
    };

    const uploadFiles = async (fileList) => {
        const files = [...fileList].slice(0, 10);
        if (uploadInProgress || files.length === 0) return;

        const invalid = files.find((file) => !['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type));
        if (invalid) {
            uploadStatus.textContent = `${invalid.name} no es una imagen JPG, PNG, WebP o GIF válida.`;
            uploadStatus.classList.add('is-error');
            return;
        }

        uploadInProgress = true;
        dropzone.classList.add('is-uploading');
        uploadProgress.hidden = false;
        uploadStatus.classList.remove('is-error');
        uploadStatus.textContent = `Subiendo ${files.length} imagen(es)…`;
        fileInput.disabled = true;
        const formData = new FormData();
        files.forEach((file) => formData.append('files[]', file));

        try {
            const response = await fetch(library.dataset.uploadUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(messageFromPayload(payload, 'No se pudieron subir las imágenes.'));

            library.querySelector('[data-media-empty]')?.remove();
            [...(payload.data ?? [])].reverse().forEach((media) => {
                grid.prepend(createMediaCard(media));
            });
            updateTotal(payload.data?.length ?? files.length);
            uploadStatus.textContent = payload.message;
            showToast(payload.message);
            uploadForm.reset();
        } catch (error) {
            uploadStatus.textContent = error.message || 'No se pudieron subir las imágenes.';
            uploadStatus.classList.add('is-error');
            showToast(uploadStatus.textContent, 'error');
        } finally {
            uploadInProgress = false;
            fileInput.disabled = false;
            dropzone.classList.remove('is-uploading', 'is-dragging');
            uploadProgress.hidden = true;
        }
    };

    const openMetadataDialog = (trigger) => {
        modalTrigger = trigger;
        isClosing = false;
        dialog.classList.remove('is-closing');
        metadataForm.action = trigger.dataset.updateUrl;
        metadataImage.src = trigger.dataset.mediaThumb;
        metadataImage.alt = trigger.dataset.mediaAlt || '';
        metadataName.textContent = trigger.dataset.mediaName;
        metadataFields.alt.value = trigger.dataset.mediaAlt || '';
        metadataFields.caption.value = trigger.dataset.mediaCaption || '';
        metadataFields.credit.value = trigger.dataset.mediaCredit || '';
        metadataFields.license.value = trigger.dataset.mediaLicense || '';
        metadataError.hidden = true;
        metadataError.textContent = '';

        if (canUseGenieMorph(reduceMotion)) dialog.style.opacity = '0';
        dialog.showModal();
        modalAnimation?.cancel();

        if (canUseGenieMorph(reduceMotion)) {
            try {
                runGenieMorph({
                    dialog,
                    trigger,
                    direction: 'open',
                    duration: 300,
                });
            } catch {
                dialog.style.removeProperty('opacity');
                modalAnimation = dialog.animate(genieFallbackFrames(dialog, trigger), {
                    duration: 300,
                    easing: 'cubic-bezier(.2, .82, .2, 1)',
                    fill: 'both',
                });
            }
        }

        window.setTimeout(() => metadataFields.alt.focus(), reduceMotion.matches ? 30 : 220);
    };

    const closeMetadataDialog = () => {
        if (!dialog.open || isClosing) return;
        if (reduceMotion.matches) {
            dialog.close();
            modalTrigger?.focus();
            return;
        }

        isClosing = true;
        dialog.classList.add('is-closing');
        modalAnimation?.cancel();
        const finish = () => {
            dialog.close();
            dialog.classList.remove('is-closing');
            isClosing = false;
            modalTrigger?.focus();
        };

        if (canUseGenieMorph(reduceMotion) && modalTrigger) {
            try {
                runGenieMorph({
                    dialog,
                    trigger: modalTrigger,
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

        modalAnimation = dialog.animate(genieFallbackFrames(dialog, modalTrigger), {
            duration: 300,
            easing: 'cubic-bezier(.55, .02, .78, .35)',
            direction: 'reverse',
            fill: 'both',
        });
        modalAnimation.addEventListener('finish', finish, { once: true });
    };

    const copyLink = async (button) => {
        const link = button.dataset.mediaUrl;
        if (!link) return;

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(link);
            } else {
                const field = document.createElement('textarea');
                field.value = link;
                field.style.position = 'fixed';
                field.style.opacity = '0';
                document.body.append(field);
                field.select();
                document.execCommand('copy');
                field.remove();
            }
            showToast('Link de la imagen copiado.');
            button.classList.add('is-copied');
            window.setTimeout(() => button.classList.remove('is-copied'), 1200);
        } catch {
            showToast('No se pudo copiar el link.', 'error');
        }
    };

    uploadForm.addEventListener('submit', (event) => {
        event.preventDefault();
        uploadFiles(fileInput.files);
    });
    fileInput.addEventListener('change', () => uploadFiles(fileInput.files));
    dropzone.addEventListener('dragenter', (event) => {
        event.preventDefault();
        dragDepth += 1;
        dropzone.classList.add('is-dragging');
    });
    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
    });
    dropzone.addEventListener('dragleave', (event) => {
        event.preventDefault();
        dragDepth = Math.max(0, dragDepth - 1);
        if (dragDepth === 0) dropzone.classList.remove('is-dragging');
    });
    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dragDepth = 0;
        dropzone.classList.remove('is-dragging');
        uploadFiles(event.dataTransfer.files);
    });

    library.addEventListener('click', (event) => {
        const edit = event.target.closest('[data-media-edit]');
        if (edit) {
            openMetadataDialog(edit);
            return;
        }

        const copy = event.target.closest('[data-media-copy]');
        if (copy) copyLink(copy);
    });
    dialog.querySelectorAll('[data-close-media-metadata]').forEach((button) => {
        button.addEventListener('click', closeMetadataDialog);
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) closeMetadataDialog();
    });
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeMetadataDialog();
    });
    metadataForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        saveMetadata.disabled = true;
        saveMetadata.textContent = 'Guardando…';
        metadataError.hidden = true;

        try {
            const response = await fetch(metadataForm.action, {
                method: 'POST',
                body: new FormData(metadataForm),
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(messageFromPayload(payload, 'No se pudieron guardar los metadatos.'));

            const media = payload.data;
            Object.assign(modalTrigger.dataset, {
                mediaAlt: media.alt_text || '',
                mediaCaption: media.caption || '',
                mediaCredit: media.credit || '',
                mediaLicense: media.license || '',
            });
            modalTrigger.closest('[data-media-card]')?.querySelector('img')?.setAttribute('alt', media.alt_text || '');
            closeMetadataDialog();
            showToast(payload.message);
        } catch (error) {
            metadataError.textContent = error.message || 'No se pudieron guardar los metadatos.';
            metadataError.hidden = false;
        } finally {
            saveMetadata.disabled = false;
            saveMetadata.textContent = 'Guardar metadatos';
        }
    });
}
