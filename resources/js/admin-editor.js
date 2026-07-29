import {
    Alignment,
    Autoformat,
    BlockQuote,
    Bold,
    ClassicEditor,
    Essentials,
    FontBackgroundColor,
    FontColor,
    Fullscreen,
    Heading,
    HorizontalLine,
    Image,
    ImageCaption,
    ImageInsert,
    ImageResize,
    ImageStyle,
    ImageTextAlternative,
    ImageToolbar,
    Indent,
    IndentBlock,
    Italic,
    Link,
    LinkImage,
    List,
    MediaEmbed,
    Paragraph,
    PasteFromOffice,
    RemoveFormat,
    SourceEditing,
    Strikethrough,
    Table,
    TableCaption,
    TableColumnResize,
    TableToolbar,
    Underline,
    WordCount,
} from 'ckeditor5';
import coreTranslations from 'ckeditor5/translations/es.js';
import 'ckeditor5/ckeditor5.css';

const editorInstances = new Map();
const licenseKey = import.meta.env.VITE_CKEDITOR_LICENSE_KEY?.trim() || 'GPL';

const wrappersWithin = (root) => {
    if (!(root instanceof Element || root instanceof Document)) return [];

    return [
        ...(root instanceof Element && root.matches('[data-ckeditor]') ? [root] : []),
        ...root.querySelectorAll('[data-ckeditor]'),
    ];
};

const updateInput = (input, wrapper, editor) => {
    input.value = editor.getData();
    input.dispatchEvent(new Event('input', { bubbles: true }));
    wrapper.dispatchEvent(new CustomEvent('ckeditor:change', {
        bubbles: true,
        detail: { data: input.value, editor },
    }));
};

const destroyEditor = async (wrapper) => {
    const record = editorInstances.get(wrapper);
    if (!record) return;

    record.events.abort();
    editorInstances.delete(wrapper);
    delete wrapper.dataset.ckeditorInitialized;

    try {
        await record.editor.destroy();
    } catch {
        // The host framework may already have detached the editable element.
    }
};

const initialiseEditor = async (wrapper) => {
    if (wrapper.dataset.ckeditorInitialized === 'true') return;

    const input = wrapper.querySelector('[data-ckeditor-input]');
    const surface = wrapper.querySelector('[data-ckeditor-surface]');
    const form = wrapper.closest('form');
    const wordCounter = wrapper.querySelector('[data-ckeditor-word-count]');
    const characterCounter = wrapper.querySelector('[data-ckeditor-character-count]');
    const autosaveStatus = wrapper.querySelector('[data-autosave-status]');

    if (!input || !surface || !form) return;

    wrapper.dataset.ckeditorInitialized = 'true';

    const draftKey = `estacionradial:post-draft:${wrapper.dataset.draftKey || 'new'}`;
    const savedDraft = window.localStorage.getItem(draftKey);
    let initialData = input.value || '<p></p>';

    if (savedDraft) {
        try {
            const parsed = JSON.parse(savedDraft);

            if (parsed.html && parsed.html !== initialData && window.confirm('Existe una copia local más reciente. ¿Deseas recuperarla?')) {
                initialData = parsed.html;
                input.value = parsed.html;
            }
        } catch {
            window.localStorage.removeItem(draftKey);
        }
    }

    try {
        const editor = await ClassicEditor.create(surface, {
            licenseKey,
            translations: [coreTranslations],
            language: {
                ui: 'es',
                content: 'es',
            },
            plugins: [
                Alignment,
                Autoformat,
                BlockQuote,
                Bold,
                Essentials,
                FontBackgroundColor,
                FontColor,
                Fullscreen,
                Heading,
                HorizontalLine,
                Image,
                ImageCaption,
                ImageInsert,
                ImageResize,
                ImageStyle,
                ImageTextAlternative,
                ImageToolbar,
                Indent,
                IndentBlock,
                Italic,
                Link,
                LinkImage,
                List,
                MediaEmbed,
                Paragraph,
                PasteFromOffice,
                RemoveFormat,
                SourceEditing,
                Strikethrough,
                Table,
                TableCaption,
                TableColumnResize,
                TableToolbar,
                Underline,
                WordCount,
            ],
            toolbar: {
                items: [
                    'undo',
                    'redo',
                    '|',
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'underline',
                    'strikethrough',
                    'fontColor',
                    'fontBackgroundColor',
                    '|',
                    'alignment',
                    'bulletedList',
                    'numberedList',
                    'outdent',
                    'indent',
                    '|',
                    'blockQuote',
                    'link',
                    'insertImage',
                    'insertTable',
                    'horizontalLine',
                    'mediaEmbed',
                    '|',
                    'removeFormat',
                    'sourceEditing',
                    'fullscreen',
                ],
                shouldNotGroupWhenFull: false,
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Párrafo', class: 'ck-heading_paragraph' },
                    { model: 'heading2', view: 'h2', title: 'Título H2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Título H3', class: 'ck-heading_heading3' },
                    { model: 'heading4', view: 'h4', title: 'Título H4', class: 'ck-heading_heading4' },
                ],
            },
            image: {
                insert: {
                    type: 'auto',
                },
                resizeOptions: [
                    { name: 'resizeImage:original', value: null, label: 'Tamaño original' },
                    { name: 'resizeImage:75', value: '75', label: '75 %' },
                    { name: 'resizeImage:50', value: '50', label: '50 %' },
                    { name: 'resizeImage:25', value: '25', label: '25 %' },
                ],
                toolbar: [
                    'imageTextAlternative',
                    'toggleImageCaption',
                    '|',
                    'imageStyle:inline',
                    'imageStyle:wrapText',
                    'imageStyle:breakText',
                    '|',
                    'resizeImage',
                    'linkImage',
                ],
            },
            table: {
                contentToolbar: [
                    'tableColumn',
                    'tableRow',
                    'mergeTableCells',
                    'toggleTableCaption',
                ],
            },
            mediaEmbed: {
                previewsInData: true,
            },
            link: {
                addTargetToExternalLinks: true,
                defaultProtocol: 'https://',
            },
            placeholder: 'Escribe el contenido completo de la noticia…',
            initialData,
            wordCount: {
                onUpdate: ({ words, characters }) => {
                    if (wordCounter) wordCounter.textContent = `${words} ${words === 1 ? 'palabra' : 'palabras'}`;
                    if (characterCounter) characterCounter.textContent = `${characters.toLocaleString('es-PE')} caracteres`;
                },
            },
        });

        const events = new AbortController();
        const signal = events.signal;
        const dialog = document.querySelector('[data-media-dialog]');
        const inlineMediaInput = form.querySelector('[name="inline_media_ids"]');
        const inlineIds = new Set((inlineMediaInput?.value || '').split(',').filter(Boolean));

        editorInstances.set(wrapper, { editor, events });
        updateInput(input, wrapper, editor);

        editor.model.document.on('change:data', () => {
            updateInput(input, wrapper, editor);
            wrapper.dataset.dirty = 'true';

            window.clearTimeout(wrapper.autosaveTimer);
            wrapper.autosaveTimer = window.setTimeout(() => {
                window.localStorage.setItem(draftKey, JSON.stringify({
                    html: editor.getData(),
                    savedAt: new Date().toISOString(),
                }));
                if (autosaveStatus) autosaveStatus.textContent = 'Copia local guardada';
            }, 1200);
        });

        editor.editing.view.document.on('blur', () => {
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        wrapper.querySelectorAll('[data-open-media-library]').forEach((button) => {
            button.addEventListener('click', () => dialog?.showModal(), { signal });
        });

        dialog?.querySelector('[data-dialog-close]')?.addEventListener('click', () => dialog.close(), { signal });
        dialog?.querySelectorAll('[data-insert-media]').forEach((button) => {
            button.addEventListener('click', () => {
                editor.execute('insertImage', {
                    source: [{
                        src: button.dataset.mediaUrl,
                        alt: button.dataset.mediaAlt,
                    }],
                });
                editor.editing.view.focus();

                inlineIds.add(button.dataset.mediaId);
                if (inlineMediaInput) inlineMediaInput.value = [...inlineIds].join(',');
                dialog.close();
            }, { signal });
        });

        form.addEventListener('submit', () => {
            updateInput(input, wrapper, editor);
            wrapper.dataset.dirty = 'false';
            window.localStorage.removeItem(draftKey);
        }, { signal });

        wrapper.dispatchEvent(new CustomEvent('ckeditor:ready', {
            bubbles: true,
            detail: { editor },
        }));
    } catch (error) {
        delete wrapper.dataset.ckeditorInitialized;
        wrapper.classList.add('ckeditor-wrapper--error');

        if (autosaveStatus) {
            autosaveStatus.textContent = 'No se pudo iniciar CKEditor. Revisa la licencia y la consola.';
        }

        console.error('CKEditor no pudo iniciarse.', error);
    }
};

export const initialiseCkEditors = (root = document) => {
    wrappersWithin(root).forEach((wrapper) => initialiseEditor(wrapper));
};

initialiseCkEditors();

document.addEventListener('livewire:initialized', () => initialiseCkEditors());
document.addEventListener('livewire:navigated', () => initialiseCkEditors());
document.addEventListener('alpine:initialized', () => initialiseCkEditors());
document.addEventListener('ckeditor:refresh', (event) => initialiseCkEditors(event.target));

new MutationObserver((mutations) => {
    for (const [wrapper] of editorInstances) {
        if (!document.documentElement.contains(wrapper)) destroyEditor(wrapper);
    }

    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => initialiseCkEditors(node));
    });
}).observe(document.documentElement, { childList: true, subtree: true });

window.addEventListener('beforeunload', (event) => {
    if (![...editorInstances.keys()].some((wrapper) => wrapper.dataset.dirty === 'true')) return;

    event.preventDefault();
    event.returnValue = '';
});
