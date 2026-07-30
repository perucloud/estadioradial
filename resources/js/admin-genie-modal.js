const animationFrames = new WeakMap();

const ensureLayer = () => {
    let layer = document.querySelector('[data-admin-genie-layer]');
    if (layer) return layer;

    layer = document.createElement('div');
    layer.className = 'admin-genie-layer';
    layer.setAttribute('popover', 'manual');
    layer.setAttribute('data-admin-genie-layer', '');
    layer.setAttribute('aria-hidden', 'true');
    layer.innerHTML = `
        <svg viewBox="0 0 ${window.innerWidth} ${window.innerHeight}" preserveAspectRatio="none">
            <defs>
                <linearGradient id="admin-genie-gradient" x1="0" y1="1" x2="0" y2="0">
                    <stop offset="0%" stop-color="#5b21b6" />
                    <stop offset="52%" stop-color="#7c3aed" />
                    <stop offset="100%" stop-color="#4f46e5" />
                </linearGradient>
                <filter id="admin-genie-glow" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="10" stdDeviation="12" flood-color="#4c1d95" flood-opacity=".3" />
                </filter>
            </defs>
            <path class="admin-genie-layer__sheet" data-genie-sheet />
            <path class="admin-genie-layer__shine" data-genie-shine />
        </svg>
    `;
    document.body.append(layer);

    return layer;
};

const lerp = (start, end, progress) => start + ((end - start) * progress);
const point = (x, y) => `${x.toFixed(2)} ${y.toFixed(2)}`;
const liquidPath = (buttonRect, dialogRect, progress) => {
    const topProgress = 1 - ((1 - progress) ** 3);
    const bottomProgress = progress ** 2;
    const topLeft = {
        x: lerp(buttonRect.left, dialogRect.left, topProgress),
        y: lerp(buttonRect.top, dialogRect.top, topProgress),
    };
    const topRight = {
        x: lerp(buttonRect.right, dialogRect.right, topProgress),
        y: lerp(buttonRect.top, dialogRect.top, topProgress),
    };
    const bottomLeft = {
        x: lerp(buttonRect.left, dialogRect.left, bottomProgress),
        y: lerp(buttonRect.bottom, dialogRect.bottom, bottomProgress),
    };
    const bottomRight = {
        x: lerp(buttonRect.right, dialogRect.right, bottomProgress),
        y: lerp(buttonRect.bottom, dialogRect.bottom, bottomProgress),
    };
    const rightControlOne = {
        x: lerp(topRight.x, bottomRight.x, .12),
        y: lerp(topRight.y, bottomRight.y, .44),
    };
    const rightControlTwo = {
        x: lerp(topRight.x, bottomRight.x, .9),
        y: lerp(topRight.y, bottomRight.y, .56),
    };
    const leftControlOne = {
        x: lerp(bottomLeft.x, topLeft.x, .12),
        y: lerp(bottomLeft.y, topLeft.y, .44),
    };
    const leftControlTwo = {
        x: lerp(bottomLeft.x, topLeft.x, .9),
        y: lerp(bottomLeft.y, topLeft.y, .56),
    };

    return [
        `M ${point(topLeft.x, topLeft.y)}`,
        `L ${point(topRight.x, topRight.y)}`,
        `C ${point(rightControlOne.x, rightControlOne.y)}, ${point(rightControlTwo.x, rightControlTwo.y)}, ${point(bottomRight.x, bottomRight.y)}`,
        `L ${point(bottomLeft.x, bottomLeft.y)}`,
        `C ${point(leftControlOne.x, leftControlOne.y)}, ${point(leftControlTwo.x, leftControlTwo.y)}, ${point(topLeft.x, topLeft.y)}`,
        'Z',
    ].join(' ');
};

export const canUseGenieMorph = (reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)')) => (
    !reduceMotion.matches
    && typeof HTMLElement.prototype.showPopover === 'function'
    && typeof window.requestAnimationFrame === 'function'
);

export const runGenieMorph = ({
    dialog,
    trigger,
    direction,
    duration,
    onFinish,
}) => {
    const layer = ensureLayer();
    const svg = layer.querySelector('svg');
    const sheet = layer.querySelector('[data-genie-sheet]');
    const shine = layer.querySelector('[data-genie-shine]');
    const triggerRect = trigger?.getBoundingClientRect() ?? dialog.getBoundingClientRect();
    const dialogRect = dialog.getBoundingClientRect();
    const startedAt = performance.now();

    svg.setAttribute('viewBox', `0 0 ${window.innerWidth} ${window.innerHeight}`);
    if (!layer.matches(':popover-open')) layer.showPopover();
    window.cancelAnimationFrame(animationFrames.get(dialog));

    const draw = (timestamp) => {
        const elapsed = Math.min(1, (timestamp - startedAt) / duration);
        const eased = direction === 'open'
            ? 1 - ((1 - elapsed) ** 3)
            : elapsed * elapsed * (3 - (2 * elapsed));
        const progress = direction === 'open' ? eased : 1 - eased;
        const path = liquidPath(triggerRect, dialogRect, progress);
        const reveal = direction === 'open'
            ? Math.max(0, (elapsed - .54) / .46)
            : Math.max(0, 1 - (elapsed / .32));
        const sheetOpacity = direction === 'open'
            ? Math.min(1, elapsed * 4) * (1 - Math.max(0, (elapsed - .7) / .3))
            : Math.min(1, elapsed * 4);

        sheet.setAttribute('d', path);
        shine.setAttribute('d', path);
        layer.style.opacity = String(sheetOpacity);
        dialog.style.opacity = String(reveal);
        dialog.style.transform = `translate(-50%, -50%) scale(${(.97 + (reveal * .03)).toFixed(3)})`;

        if (elapsed < 1) {
            animationFrames.set(dialog, window.requestAnimationFrame(draw));
            return;
        }

        layer.hidePopover();
        layer.style.removeProperty('opacity');
        dialog.style.removeProperty('opacity');
        dialog.style.removeProperty('transform');
        animationFrames.delete(dialog);
        onFinish?.();
    };

    animationFrames.set(dialog, window.requestAnimationFrame(draw));
};

export const genieFallbackFrames = (dialog, trigger) => {
    const dialogRect = dialog.getBoundingClientRect();
    const triggerRect = trigger?.getBoundingClientRect();
    const originX = triggerRect
        ? triggerRect.left + (triggerRect.width / 2) - (dialogRect.left + (dialogRect.width / 2))
        : 0;
    const originY = triggerRect
        ? triggerRect.top + (triggerRect.height / 2) - (dialogRect.top + (dialogRect.height / 2))
        : 18;
    const scaleX = triggerRect
        ? Math.max(.2, Math.min(.55, triggerRect.width / dialogRect.width))
        : .9;
    const scaleY = triggerRect
        ? Math.max(.08, Math.min(.22, triggerRect.height / dialogRect.height))
        : .9;

    return [
        {
            opacity: .08,
            clipPath: 'inset(34% 0 34% 0 round 18px)',
            transform: `translate(-50%, -50%) translate(${originX}px, ${originY}px) scale(${scaleX}, ${scaleY})`,
            offset: 0,
        },
        {
            opacity: .72,
            clipPath: 'inset(9% 0 9% 0 round 18px)',
            transform: `translate(-50%, -50%) translate(${originX * .28}px, ${originY * .24}px) scale(.74, 1.08)`,
            offset: .48,
        },
        {
            opacity: 1,
            clipPath: 'inset(0 0 0 0 round 18px)',
            transform: 'translate(-50%, -50%) scale(1.035, .97)',
            offset: .76,
        },
        {
            opacity: 1,
            clipPath: 'inset(0 0 0 0 round 18px)',
            transform: 'translate(-50%, -50%) scale(1)',
            offset: 1,
        },
    ];
};
