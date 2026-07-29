const truncateAtWord = (value, limit) => {
    const text = value.replace(/\s+/g, ' ').trim();
    if (text.length <= limit) return text;

    const shortened = text.slice(0, Math.max(0, limit - 1));
    const lastSpace = shortened.lastIndexOf(' ');

    return `${(lastSpace > limit * 0.65 ? shortened.slice(0, lastSpace) : shortened).trim()}…`;
};

document.querySelectorAll('.post-editor-form').forEach((form) => {
    const title = form.querySelector('[data-seo-title-source]');
    const excerpt = form.querySelector('[data-excerpt-input]');
    const seoTitle = form.querySelector('[data-seo-title-input]');
    const seoDescription = form.querySelector('[data-seo-description-input]');

    const bindGeneratedField = (source, target, limit) => {
        if (!source || !target) return;

        let generated = target.dataset.seoGenerated === 'true';
        const synchronize = () => {
            if (!generated) return;
            target.value = truncateAtWord(source.value, limit);
        };

        target.addEventListener('input', (event) => {
            if (!event.isTrusted) return;
            generated = target.value.trim() === '';
            target.dataset.seoGenerated = generated ? 'true' : 'false';
            if (generated) synchronize();
        });
        source.addEventListener('input', synchronize);
        synchronize();
    };

    bindGeneratedField(title, seoTitle, 70);
    bindGeneratedField(excerpt, seoDescription, 170);
});
