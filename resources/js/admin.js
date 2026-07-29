import { Editor } from '@tiptap/core';
import CharacterCount from '@tiptap/extension-character-count';
import Highlight from '@tiptap/extension-highlight';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import { TableKit } from '@tiptap/extension-table';
import TextAlign from '@tiptap/extension-text-align';
import { Color, TextStyle } from '@tiptap/extension-text-style';
import Typography from '@tiptap/extension-typography';
import Youtube from '@tiptap/extension-youtube';
import StarterKit from '@tiptap/starter-kit';

const normaliseUrl = (value) => {
    const url = value.trim();

    if (/^(https?:\/\/|mailto:|tel:|\/)/i.test(url)) return url;

    return `https://${url}`;
};

const adminNavGroups = [...document.querySelectorAll('[data-admin-nav-group]')];
const desktopFlyoutQuery = window.matchMedia('(min-width: 961px)');

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

document.querySelectorAll('[data-tiptap]').forEach((wrapper) => {
    const input = wrapper.querySelector('[data-tiptap-input]');
    const surface = wrapper.querySelector('[data-tiptap-surface]');
    const counter = wrapper.querySelector('[data-tiptap-count]');
    const characterCounter = wrapper.querySelector('[data-tiptap-character-count]');
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

    const updateCounters = (currentEditor) => {
        const words = currentEditor.storage.characterCount.words();
        const characters = currentEditor.storage.characterCount.characters();

        if (counter) counter.textContent = `${words} ${words === 1 ? 'palabra' : 'palabras'}`;
        if (characterCounter) characterCounter.textContent = `${characters.toLocaleString('es-PE')} caracteres`;
    };

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
            TextStyle,
            Color,
            Highlight.configure({
                multicolor: true,
            }),
            TextAlign.configure({
                types: ['heading', 'paragraph'],
            }),
            Typography,
            Youtube.configure({
                nocookie: true,
                modestBranding: true,
                width: 800,
                height: 450,
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
            updateCounters(currentEditor);

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

    updateCounters(editor);

    const updateToolbar = () => {
        wrapper.querySelectorAll('[data-editor-command]').forEach((button) => {
            const command = button.dataset.editorCommand;
            const headingLevel = /^heading([234])$/.exec(command)?.[1];
            const attributes = headingLevel
                ? ['heading', { level: Number(headingLevel) }]
                : [command];
            button.classList.toggle('is-active', editor.isActive(...attributes));
        });

        wrapper.querySelectorAll('[data-editor-align]').forEach((button) => {
            button.classList.toggle('is-active', editor.isActive({ textAlign: button.dataset.editorAlign }));
        });

        wrapper.querySelectorAll('[data-table-command]').forEach((button) => {
            button.disabled = !editor.isActive('table');
        });

        const blockSelect = wrapper.querySelector('[data-editor-block]');
        if (blockSelect) {
            blockSelect.value = [2, 3, 4]
                .map((level) => [`heading${level}`, editor.isActive('heading', { level })])
                .find(([, active]) => active)?.[0]
                || (editor.isActive('codeBlock') ? 'codeBlock' : 'paragraph');
        }
    };

    editor.on('selectionUpdate', updateToolbar);
    editor.on('transaction', updateToolbar);
    updateToolbar();

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
                heading4: () => chain.toggleHeading({ level: 4 }).run(),
                paragraph: () => chain.setParagraph().run(),
                bulletList: () => chain.toggleBulletList().run(),
                orderedList: () => chain.toggleOrderedList().run(),
                blockquote: () => chain.toggleBlockquote().run(),
                codeBlock: () => chain.toggleCodeBlock().run(),
                horizontalRule: () => chain.setHorizontalRule().run(),
                undo: () => chain.undo().run(),
                redo: () => chain.redo().run(),
                table: () => chain.insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
                addRowAfter: () => chain.addRowAfter().run(),
                addColumnAfter: () => chain.addColumnAfter().run(),
                deleteRow: () => chain.deleteRow().run(),
                deleteColumn: () => chain.deleteColumn().run(),
                deleteTable: () => chain.deleteTable().run(),
            };

            commands[command]?.();
        });
    });

    wrapper.querySelector('[data-editor-block]')?.addEventListener('change', (event) => {
        const command = event.target.value;
        const chain = editor.chain().focus();

        if (command === 'paragraph') chain.setParagraph().run();
        if (command === 'codeBlock') chain.toggleCodeBlock().run();
        if (/^heading[234]$/.test(command)) {
            chain.setHeading({ level: Number(command.slice(-1)) }).run();
        }
    });

    wrapper.querySelectorAll('[data-editor-align]').forEach((button) => {
        button.addEventListener('click', () => {
            editor.chain().focus().setTextAlign(button.dataset.editorAlign).run();
        });
    });

    wrapper.querySelector('[data-editor-color]')?.addEventListener('input', (event) => {
        editor.chain().focus().setColor(event.target.value).run();
    });

    wrapper.querySelector('[data-editor-highlight]')?.addEventListener('input', (event) => {
        editor.chain().focus().setHighlight({ color: event.target.value }).run();
    });

    wrapper.querySelectorAll('[data-editor-link]').forEach((button) => button.addEventListener('click', () => {
        const previous = editor.getAttributes('link').href || '';
        const value = window.prompt('Dirección del enlace', previous);

        if (value === null) return;
        if (value.trim() === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            return;
        }

        editor.chain().focus().extendMarkRange('link').setLink({ href: normaliseUrl(value) }).run();
    }));

    wrapper.querySelector('[data-editor-video]')?.addEventListener('click', () => {
        const value = window.prompt('Pega la dirección de un video de YouTube');
        if (!value) return;

        try {
            const url = new URL(normaliseUrl(value));
            const allowedHosts = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'music.youtube.com'];

            if (!allowedHosts.includes(url.hostname.toLowerCase())) throw new Error('unsupported-host');

            editor.chain().focus().setYoutubeVideo({ src: url.toString(), width: 800, height: 450 }).run();
        } catch {
            window.alert('Ingresa una dirección válida de YouTube.');
        }
    });

    wrapper.querySelector('[data-editor-clear]')?.addEventListener('click', () => {
        editor.chain().focus().unsetAllMarks().clearNodes().run();
    });

    wrapper.querySelector('[data-editor-fullscreen]')?.addEventListener('click', () => {
        const enabled = wrapper.classList.toggle('is-fullscreen');
        document.body.classList.toggle('has-editor-fullscreen', enabled);
        wrapper.querySelector('[data-editor-fullscreen] span').textContent = enabled
            ? 'Salir de pantalla completa'
            : 'Pantalla completa';
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !wrapper.classList.contains('is-fullscreen')) return;

        wrapper.classList.remove('is-fullscreen');
        document.body.classList.remove('has-editor-fullscreen');
        wrapper.querySelector('[data-editor-fullscreen] span').textContent = 'Pantalla completa';
    });

    const dialog = document.querySelector('[data-media-dialog]');
    const inlineMediaInput = form.querySelector('[name="inline_media_ids"]');
    const inlineIds = new Set((inlineMediaInput?.value || '').split(',').filter(Boolean));

    wrapper.querySelectorAll('[data-editor-image]').forEach((button) => {
        button.addEventListener('click', () => dialog?.showModal());
    });
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
