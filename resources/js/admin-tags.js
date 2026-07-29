const normalize = (value) => value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/\s+/g, ' ')
    .trim();

const splitTags = (value) => value
    .split(/[,;\n]+/)
    .map((tag) => tag.trim())
    .filter(Boolean);

document.querySelectorAll('[data-tag-editor]').forEach((editor) => {
    const form = editor.closest('form');
    const input = editor.querySelector('[data-tag-input]');
    const suggestions = editor.querySelector('[data-tag-suggestions]');
    const source = editor.querySelector('[data-tag-source]');
    if (!form || !input || !suggestions || !source) return;

    let available = [];
    try {
        available = JSON.parse(source.textContent);
    } catch {
        available = [];
    }

    const title = form.querySelector('[name="title"]');
    const excerpt = form.querySelector('[name="excerpt"]');
    const body = form.querySelector('[name="body"]');
    const category = form.querySelector('[data-tag-category]');

    const addTag = (tag) => {
        const current = splitTags(input.value);
        if (!current.some((item) => normalize(item) === normalize(tag))) current.push(tag);
        input.value = current.join(', ');
        input.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const render = () => {
        const selected = new Set(splitTags(input.value).map(normalize));
        const titleText = normalize(title?.value ?? '');
        const excerptText = normalize(excerpt?.value ?? '');
        const bodyText = normalize((body?.value ?? '').replace(/<[^>]*>/g, ' '));
        const categoryText = category?.selectedOptions[0]?.textContent?.trim() ?? '';
        const content = `${titleText} ${excerptText} ${bodyText}`.trim();

        const candidates = [...new Set([categoryText, ...available].filter(Boolean))]
            .filter((tag) => !selected.has(normalize(tag)))
            .map((tag, index) => {
                const normalizedTag = normalize(tag);
                const words = normalizedTag.split(' ').filter((word) => word.length > 3);
                let score = Math.max(0, available.length - index) / Math.max(available.length, 1);

                if (titleText.includes(normalizedTag)) score += 12;
                if (excerptText.includes(normalizedTag)) score += 8;
                if (bodyText.includes(normalizedTag)) score += 5;
                if (normalize(categoryText) === normalizedTag) score += 10;
                score += words.filter((word) => content.includes(word)).length * 2;

                return { tag, score };
            })
            .filter(({ score }) => score >= 2)
            .sort((left, right) => right.score - left.score)
            .slice(0, 6);

        suggestions.replaceChildren();
        if (candidates.length === 0) {
            const empty = document.createElement('small');
            empty.textContent = 'Las sugerencias aparecerán al escribir la noticia.';
            suggestions.append(empty);
            return;
        }

        candidates.forEach(({ tag }) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'tag-suggestion';
            button.textContent = `+ ${tag}`;
            button.addEventListener('click', () => addTag(tag));
            suggestions.append(button);
        });
    };

    let timer;
    const scheduleRender = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(render, 180);
    };

    [title, excerpt, body, input].filter(Boolean).forEach((element) => {
        element.addEventListener('input', scheduleRender);
    });
    category?.addEventListener('change', render);
    document.addEventListener('ckeditor:change', scheduleRender);
    render();
});
