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

document.querySelectorAll('[data-hero-rotator]').forEach((rotator) => {
    const slides = [...rotator.querySelectorAll('[data-hero-slide]')];
    const previous = rotator.querySelector('[data-hero-prev]');
    const next = rotator.querySelector('[data-hero-next]');
    const dots = [...rotator.querySelectorAll('[data-hero-dot]')];
    const pauseButton = rotator.querySelector('[data-hero-pause]');
    const status = rotator.querySelector('[data-hero-status]');

    if (slides.length < 2) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const finePointer = window.matchMedia('(pointer: fine)');
    const automaticMode = rotator.dataset.heroMode === 'automatic';
    const shouldLoop = rotator.dataset.heroLoop !== 'false';
    const parallaxEnabled = rotator.dataset.heroParallax !== 'false';
    const pauseOnHover = rotator.dataset.heroPauseHover !== 'false';
    const swipeEnabled = rotator.dataset.heroSwipe !== 'false';
    const preloadEnabled = rotator.dataset.heroPreload !== 'false';
    const visibleOnly = rotator.dataset.heroVisibleOnly !== 'false';
    const pauseWhenHidden = rotator.dataset.heroPauseHidden !== 'false';
    const resetAfterManual = rotator.dataset.heroResetManual !== 'false';
    const reduceOnMobile = rotator.dataset.heroReduceMobile !== 'false';
    const transitionDuration = Math.min(1500, Math.max(
        300,
        Number.parseInt(rotator.dataset.heroTransitionDuration ?? '', 10) || 800,
    ));
    const smallScreen = window.matchMedia('(max-width: 680px)');
    const configuredInterval = Number.parseInt(rotator.dataset.heroInterval ?? '', 10);
    const interval = Number.isFinite(configuredInterval)
        ? Math.max(4000, configuredInterval)
        : 8000;
    const pauseReasons = new Set();
    let current = 0;
    let timer;
    let pausedByUser = false;
    let visible = true;
    let pointerStart;

    const updateControls = () => {
        if (previous) previous.disabled = !shouldLoop && current === 0;
        if (next) next.disabled = !shouldLoop && current === slides.length - 1;

        dots.forEach((dot, index) => {
            const active = index === current;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-current', String(active));
        });

        if (pauseButton) {
            pauseButton.hidden = !automaticMode || reducedMotion.matches;
            pauseButton.setAttribute('aria-pressed', String(pausedByUser));
            pauseButton.setAttribute(
                'aria-label',
                pausedByUser ? 'Reanudar rotación automática' : 'Pausar rotación automática',
            );
            pauseButton.querySelector('span').textContent = pausedByUser ? '▶' : 'Ⅱ';
        }
    };

    const stopAutoplay = () => {
        window.clearTimeout(timer);
        timer = undefined;
    };

    const autoplayAllowed = () => automaticMode
        && !reducedMotion.matches
        && !pausedByUser
        && pauseReasons.size === 0
        && (!pauseWhenHidden || !document.hidden)
        && (!visibleOnly || visible);

    const scheduleAutoplay = () => {
        stopAutoplay();

        if (!autoplayAllowed()) return;

        timer = window.setTimeout(() => {
            show(current + 1);
        }, interval);
    };

    const normaliseIndex = (requested) => {
        if (shouldLoop) {
            return (requested + slides.length) % slides.length;
        }

        return Math.min(slides.length - 1, Math.max(0, requested));
    };

    const prepareNextImage = () => {
        if (!preloadEnabled) return;
        const nextIndex = normaliseIndex(current + 1);
        slides[nextIndex]?.querySelectorAll('[data-hero-image]').forEach((image) => {
            image.loading = 'eager';
        });
    };

    const show = (requested, announce = false, manual = false) => {
        const target = normaliseIndex(requested);

        if (target === current && requested !== current) {
            stopAutoplay();
            updateControls();
            return;
        }

        const outgoing = slides[current];
        const incoming = slides[target];
        const backwards = requested < current && !(current === 0 && target === slides.length - 1);
        const direction = backwards ? 'backward' : 'forward';

        slides.forEach((slide) => slide.classList.remove(
            'is-entering-forward',
            'is-entering-backward',
            'is-leaving-forward',
            'is-leaving-backward',
        ));
        outgoing.classList.add(`is-leaving-${direction}`);
        incoming.classList.add(`is-entering-${direction}`);
        outgoing.classList.remove('is-active');
        outgoing.setAttribute('aria-hidden', 'true');
        outgoing.inert = true;

        current = target;

        incoming.classList.add('is-active');
        incoming.setAttribute('aria-hidden', 'false');
        incoming.inert = false;
        rotator.style.setProperty('--hero-parallax-x', '0px');
        rotator.style.setProperty('--hero-parallax-y', '0px');

        if (announce && status) {
            status.textContent = `Noticia ${current + 1} de ${slides.length}`;
        }

        updateControls();
        prepareNextImage();
        window.setTimeout(() => {
            outgoing.classList.remove(`is-leaving-${direction}`);
            incoming.classList.remove(`is-entering-${direction}`);
        }, transitionDuration + 80);

        if (!manual || resetAfterManual) scheduleAutoplay();
    };

    previous?.addEventListener('click', () => show(current - 1, true, true));
    next?.addEventListener('click', () => show(current + 1, true, true));
    dots.forEach((dot) => {
        dot.addEventListener('click', () => show(Number.parseInt(dot.dataset.heroDot, 10), true, true));
    });

    pauseButton?.addEventListener('click', () => {
        pausedByUser = !pausedByUser;
        updateControls();
        scheduleAutoplay();
    });

    rotator.addEventListener('mouseenter', () => {
        if (!pauseOnHover) return;
        pauseReasons.add('hover');
        stopAutoplay();
    });
    rotator.addEventListener('mouseleave', () => {
        if (pauseOnHover) pauseReasons.delete('hover');
        scheduleAutoplay();
        rotator.style.setProperty('--hero-parallax-x', '0px');
        rotator.style.setProperty('--hero-parallax-y', '0px');
    });
    rotator.addEventListener('focusin', () => {
        pauseReasons.add('focus');
        stopAutoplay();
    });
    rotator.addEventListener('focusout', (event) => {
        if (rotator.contains(event.relatedTarget)) return;
        pauseReasons.delete('focus');
        scheduleAutoplay();
    });
    rotator.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            show(current - 1, true, true);
        }

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            show(current + 1, true, true);
        }
    });
    rotator.addEventListener('pointerdown', (event) => {
        if (!swipeEnabled) return;
        pointerStart = event.clientX;
        pauseReasons.add('pointer');
        stopAutoplay();
    });
    rotator.addEventListener('pointerup', (event) => {
        if (!swipeEnabled) return;
        if (Number.isFinite(pointerStart)) {
            const distance = event.clientX - pointerStart;

            if (Math.abs(distance) > 55) {
                show(current + (distance < 0 ? 1 : -1), true, true);
            }
        }

        pointerStart = undefined;
        pauseReasons.delete('pointer');
        scheduleAutoplay();
    });
    rotator.addEventListener('pointermove', (event) => {
        if (!parallaxEnabled || reducedMotion.matches || !finePointer.matches) return;
        if (reduceOnMobile && smallScreen.matches) return;

        const bounds = rotator.getBoundingClientRect();
        const x = ((event.clientX - bounds.left) / bounds.width) - .5;
        const y = ((event.clientY - bounds.top) / bounds.height) - .5;

        rotator.style.setProperty('--hero-parallax-x', `${x * -12}px`);
        rotator.style.setProperty('--hero-parallax-y', `${y * -7}px`);
    });

    document.addEventListener('visibilitychange', () => {
        if (pauseWhenHidden) scheduleAutoplay();
    });
    reducedMotion.addEventListener('change', () => {
        updateControls();
        scheduleAutoplay();
    });

    if (visibleOnly && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            scheduleAutoplay();
        }, { threshold: 0.2 });

        observer.observe(rotator);
    }

    updateControls();
    prepareNextImage();
    scheduleAutoplay();
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

document.querySelectorAll('[data-sidebar-layout]').forEach((layout) => {
    const main = layout.querySelector('[data-sidebar-main]');
    const sidebar = layout.querySelector('[data-adaptive-sidebar]');
    const desktop = window.matchMedia('(min-width: 981px)');

    if (!main || !sidebar) return;

    let frame;

    const reset = () => {
        sidebar.hidden = false;
        layout.classList.remove('is-sidebar-empty');
        sidebar.querySelectorAll('[data-sidebar-module], [data-sidebar-item]').forEach((element) => {
            element.hidden = false;
        });
    };

    const fitToMainContent = () => {
        window.cancelAnimationFrame(frame);
        frame = window.requestAnimationFrame(() => {
            reset();

            if (!desktop.matches) return;

            const maximumHeight = Math.floor(main.getBoundingClientRect().height);
            const modules = [...sidebar.querySelectorAll('[data-sidebar-module]')];

            while (sidebar.scrollHeight > maximumHeight + 4) {
                const visibleModules = modules.filter((module) => !module.hidden);

                if (visibleModules.length <= 1) break;

                visibleModules.at(-1).hidden = true;
            }

            const primaryModule = modules.find((module) => !module.hidden);

            if (!primaryModule) return;

            const primaryItems = [...primaryModule.querySelectorAll('[data-sidebar-item]')];

            while (sidebar.scrollHeight > maximumHeight + 4) {
                const visibleItems = primaryItems.filter((item) => !item.hidden);

                if (visibleItems.length <= 2) break;

                visibleItems.at(-1).hidden = true;
            }

            if (sidebar.scrollHeight > maximumHeight + 4) {
                sidebar.hidden = true;
                layout.classList.add('is-sidebar-empty');
            }
        });
    };

    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(fitToMainContent);
        observer.observe(main);
    } else {
        window.addEventListener('resize', fitToMainContent);
    }
    desktop.addEventListener('change', fitToMainContent);
    window.addEventListener('load', fitToMainContent, { once: true });
    fitToMainContent();
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
