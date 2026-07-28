import { Editor } from '@tiptap/core';
import CharacterCount from '@tiptap/extension-character-count';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import { TableKit } from '@tiptap/extension-table';
import StarterKit from '@tiptap/starter-kit';

const normaliseUrl = (value) => {
    const url = value.trim();

    if (/^(https?:\/\/|mailto:|tel:|\/)/i.test(url)) return url;

    return `https://${url}`;
};

document.querySelectorAll('[data-tiptap]').forEach((wrapper) => {
    const input = wrapper.querySelector('[data-tiptap-input]');
    const surface = wrapper.querySelector('[data-tiptap-surface]');
    const counter = wrapper.querySelector('[data-tiptap-count]');
    const autosaveStatus = wrapper.querySelector('[data-autosave-status]');
    const form = wrapper.closest('form');
    const draftKey = `estacionradial:post-draft:${wrapper.dataset.draftKey || 'new'}`;

    if (!input || !surface || !form) return;

    const savedDraft = window.localStorage.getItem(draftKey);
    let initialContent = input.value || '<p></p>';

    if (savedDraft) {
        try {
            const parsed = JSON.parse(savedDraft);

            if (parsed.html && parsed.html !== initialContent && window.confirm('Existe una copia local más reciente. ¿Deseas recuperarla?')) {
                initialContent = parsed.html;
                input.value = parsed.html;
            }
        } catch {
            window.localStorage.removeItem(draftKey);
        }
    }

    const editor = new Editor({
        element: surface,
        content: initialContent,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: {
                    openOnClick: false,
                    autolink: true,
                    defaultProtocol: 'https',
                    HTMLAttributes: {
                        rel: 'noopener noreferrer nofollow',
                    },
                },
            }),
            TableKit.configure({
                table: { resizable: true },
            }),
            Image.configure({
                inline: false,
                allowBase64: false,
            }),
            Placeholder.configure({
                placeholder: 'Escribe el contenido completo de la noticia…',
            }),
            CharacterCount.configure({ limit: 250000 }),
        ],
        editorProps: {
            attributes: {
                class: 'tiptap-document',
                spellcheck: 'true',
                'aria-label': 'Contenido de la noticia',
            },
        },
        onUpdate: ({ editor: currentEditor }) => {
            input.value = currentEditor.getHTML();
            wrapper.dataset.dirty = 'true';
            counter.textContent = `${currentEditor.storage.characterCount.words()} palabras`;

            window.clearTimeout(wrapper.autosaveTimer);
            wrapper.autosaveTimer = window.setTimeout(() => {
                window.localStorage.setItem(draftKey, JSON.stringify({
                    html: currentEditor.getHTML(),
                    savedAt: new Date().toISOString(),
                }));
                autosaveStatus.textContent = 'Copia local guardada';
            }, 1200);
        },
    });

    counter.textContent = `${editor.storage.characterCount.words()} palabras`;

    const updateToolbar = () => {
        wrapper.querySelectorAll('[data-editor-command]').forEach((button) => {
            const command = button.dataset.editorCommand;
            const attributes = command === 'heading2'
                ? ['heading', { level: 2 }]
                : command === 'heading3'
                    ? ['heading', { level: 3 }]
                    : [command];
            button.classList.toggle('is-active', editor.isActive(...attributes));
        });
    };

    editor.on('selectionUpdate', updateToolbar);
    editor.on('transaction', updateToolbar);

    wrapper.querySelectorAll('[data-editor-command]').forEach((button) => {
        button.addEventListener('click', () => {
            const command = button.dataset.editorCommand;
            const chain = editor.chain().focus();

            const commands = {
                bold: () => chain.toggleBold().run(),
                italic: () => chain.toggleItalic().run(),
                underline: () => chain.toggleUnderline().run(),
                strike: () => chain.toggleStrike().run(),
                heading2: () => chain.toggleHeading({ level: 2 }).run(),
                heading3: () => chain.toggleHeading({ level: 3 }).run(),
                bulletList: () => chain.toggleBulletList().run(),
                orderedList: () => chain.toggleOrderedList().run(),
                blockquote: () => chain.toggleBlockquote().run(),
                horizontalRule: () => chain.setHorizontalRule().run(),
                undo: () => chain.undo().run(),
                redo: () => chain.redo().run(),
                table: () => chain.insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
            };

            commands[command]?.();
        });
    });

    wrapper.querySelector('[data-editor-link]')?.addEventListener('click', () => {
        const previous = editor.getAttributes('link').href || '';
        const value = window.prompt('Dirección del enlace', previous);

        if (value === null) return;
        if (value.trim() === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            return;
        }

        editor.chain().focus().extendMarkRange('link').setLink({ href: normaliseUrl(value) }).run();
    });

    const dialog = document.querySelector('[data-media-dialog]');
    const inlineMediaInput = form.querySelector('[name="inline_media_ids"]');
    const inlineIds = new Set((inlineMediaInput?.value || '').split(',').filter(Boolean));

    wrapper.querySelector('[data-editor-image]')?.addEventListener('click', () => dialog?.showModal());
    dialog?.querySelector('[data-dialog-close]')?.addEventListener('click', () => dialog.close());
    dialog?.querySelectorAll('[data-insert-media]').forEach((button) => {
        button.addEventListener('click', () => {
            editor.chain().focus().setImage({
                src: button.dataset.mediaUrl,
                alt: button.dataset.mediaAlt,
                title: button.dataset.mediaCaption || button.dataset.mediaAlt,
            }).run();
            inlineIds.add(button.dataset.mediaId);
            inlineMediaInput.value = [...inlineIds].join(',');
            dialog.close();
        });
    });

    form.addEventListener('submit', () => {
        input.value = editor.getHTML();
        wrapper.dataset.dirty = 'false';
        window.localStorage.removeItem(draftKey);
    });

    window.addEventListener('beforeunload', (event) => {
        if (wrapper.dataset.dirty !== 'true') return;
        event.preventDefault();
        event.returnValue = '';
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
