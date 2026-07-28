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
