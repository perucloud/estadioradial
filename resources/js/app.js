const menuButton = document.querySelector('.menu-toggle');
const menuPanel = document.querySelector('#header-menu-panel');
const searchButton = document.querySelector('.search-toggle');
const searchPanel = document.querySelector('#header-search');

const closePanel = (button, panel) => {
    if (!button || !panel) return;

    button.setAttribute('aria-expanded', 'false');
    panel.hidden = true;
};

const togglePanel = (button, panel, otherButton, otherPanel) => {
    if (!button || !panel) return;

    const willOpen = button.getAttribute('aria-expanded') !== 'true';
    closePanel(otherButton, otherPanel);
    button.setAttribute('aria-expanded', String(willOpen));
    panel.hidden = !willOpen;
};

if (menuButton && menuPanel) {
    menuButton.addEventListener('click', () => {
        togglePanel(menuButton, menuPanel, searchButton, searchPanel);
    });
}

if (searchButton && searchPanel) {
    searchButton.addEventListener('click', () => {
        togglePanel(searchButton, searchPanel, menuButton, menuPanel);

        if (searchButton.getAttribute('aria-expanded') === 'true') {
            window.requestAnimationFrame(() => searchPanel.querySelector('input')?.focus());
        }
    });
}

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closePanel(menuButton, menuPanel);
        closePanel(searchButton, searchPanel);
    }
});

document.querySelectorAll('[data-news-slider]').forEach((slider) => {
    const section = slider.closest('.most-viewed');
    const track = slider.querySelector('[data-slider-track]');
    const previous = section?.querySelector('[data-slider-prev]');
    const next = section?.querySelector('[data-slider-next]');
    const autoplayToggle = section?.querySelector('[data-slider-autoplay-toggle]');

    if (!track || !previous || !next) return;

    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    const automaticMode = slider.dataset.sliderMode === 'automatic';
    const shouldLoop = slider.dataset.sliderLoop !== 'false';
    const configuredInterval = Number.parseInt(slider.dataset.sliderInterval ?? '', 10);
    const interval = Number.isFinite(configuredInterval)
        ? Math.max(3000, configuredInterval)
        : 6000;
    let timer;
    let pausedByUser = false;
    let interactionPaused = false;
    let sliderIsVisible = true;

    const updateControls = () => {
        const maximum = track.scrollWidth - track.clientWidth;
        previous.disabled = track.scrollLeft <= 4;
        next.disabled = track.scrollLeft >= maximum - 4;
    };

    const move = (direction, wrap = false) => {
        const maximum = track.scrollWidth - track.clientWidth;
        const atStart = track.scrollLeft <= 4;
        const atEnd = track.scrollLeft >= maximum - 4;

        if (wrap && direction > 0 && atEnd) {
            track.scrollTo({ left: 0, behavior: 'smooth' });
            return;
        }

        if (wrap && direction < 0 && atStart) {
            track.scrollTo({ left: maximum, behavior: 'smooth' });
            return;
        }

        track.scrollBy({
            left: direction * track.clientWidth * 0.86,
            behavior: 'smooth',
        });
    };

    const autoplayAllowed = () => automaticMode
        && !mediaQuery.matches
        && !pausedByUser
        && !interactionPaused
        && !document.hidden
        && sliderIsVisible
        && track.scrollWidth > track.clientWidth + 4;

    const stopAutoplay = () => {
        window.clearTimeout(timer);
        timer = undefined;
    };

    const scheduleAutoplay = () => {
        stopAutoplay();

        if (!autoplayAllowed()) return;

        timer = window.setTimeout(() => {
            move(1, shouldLoop);
            scheduleAutoplay();
        }, interval);
    };

    const updateAutoplayToggle = () => {
        if (!autoplayToggle) return;

        autoplayToggle.hidden = !automaticMode || mediaQuery.matches;
        autoplayToggle.textContent = pausedByUser ? '▶' : 'Ⅱ';
        autoplayToggle.setAttribute('aria-pressed', String(pausedByUser));
        autoplayToggle.setAttribute(
            'aria-label',
            pausedByUser ? 'Reanudar movimiento automático' : 'Pausar movimiento automático',
        );
    };

    const handleManualMove = (direction) => {
        move(direction);
        scheduleAutoplay();
    };

    previous.addEventListener('click', () => handleManualMove(-1));
    next.addEventListener('click', () => handleManualMove(1));
    autoplayToggle?.addEventListener('click', () => {
        pausedByUser = !pausedByUser;
        updateAutoplayToggle();
        scheduleAutoplay();
    });

    section?.addEventListener('mouseenter', () => {
        interactionPaused = true;
        stopAutoplay();
    });
    section?.addEventListener('mouseleave', () => {
        interactionPaused = section.contains(document.activeElement);
        scheduleAutoplay();
    });
    section?.addEventListener('focusin', () => {
        interactionPaused = true;
        stopAutoplay();
    });
    section?.addEventListener('focusout', (event) => {
        if (section.contains(event.relatedTarget)) return;
        interactionPaused = section.matches(':hover');
        scheduleAutoplay();
    });
    track.addEventListener('pointerdown', () => {
        interactionPaused = true;
        stopAutoplay();
    });
    track.addEventListener('pointerup', () => {
        interactionPaused = section?.matches(':hover')
            || section?.contains(document.activeElement);
        scheduleAutoplay();
    });
    track.addEventListener('scroll', updateControls, { passive: true });
    window.addEventListener('resize', () => {
        updateControls();
        scheduleAutoplay();
    });
    document.addEventListener('visibilitychange', scheduleAutoplay);
    mediaQuery.addEventListener('change', () => {
        updateAutoplayToggle();
        scheduleAutoplay();
    });

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(([entry]) => {
            sliderIsVisible = entry.isIntersecting;
            scheduleAutoplay();
        }, { threshold: 0.2 });

        observer.observe(slider);
    }

    updateControls();
    updateAutoplayToggle();
    scheduleAutoplay();
});

const player = document.querySelector('[data-player]');

if (player) {
    const audio = player.querySelector('[data-player-audio]');
    const toggle = player.querySelector('[data-player-toggle]');
    const icon = player.querySelector('[data-player-icon]');
    const status = player.querySelector('[data-player-status]');

    if (audio && toggle) {
        const setState = (playing) => {
            icon.textContent = playing ? '❚❚' : '▶';
            toggle.setAttribute('aria-label', playing ? 'Pausar radio' : 'Reproducir radio');
            status.textContent = playing ? 'Reproduciendo señal en vivo' : 'Transmisión en pausa';
        };

        toggle.addEventListener('click', async () => {
            if (audio.paused) {
                status.textContent = 'Conectando con la señal…';

                try {
                    await audio.play();
                } catch {
                    status.textContent = 'No fue posible conectar con la transmisión';
                }
            } else {
                audio.pause();
            }
        });

        audio.addEventListener('playing', () => setState(true));
        audio.addEventListener('pause', () => setState(false));
        audio.addEventListener('error', () => {
            setState(false);
            status.textContent = 'La señal no está disponible en este momento';
        });

        if ('mediaSession' in navigator) {
            navigator.mediaSession.metadata = new MediaMetadata({
                title: 'Señal en vivo',
                artist: 'Estación Radial',
                album: 'Radio en línea',
            });
            navigator.mediaSession.setActionHandler('play', () => audio.play());
            navigator.mediaSession.setActionHandler('pause', () => audio.pause());
        }
    }
}
