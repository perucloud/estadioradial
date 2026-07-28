const menuButton = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#main-navigation');

if (menuButton && navigation) {
    menuButton.addEventListener('click', () => {
        const isOpen = navigation.classList.toggle('is-open');
        menuButton.setAttribute('aria-expanded', String(isOpen));
    });
}

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
