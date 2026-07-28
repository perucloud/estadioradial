@props(['stream'])

<aside class="sticky-player" data-player>
    <div class="container sticky-player__inner">
        <div class="player-identity">
            <span class="player-identity__live">EN VIVO</span>
            <strong>{{ $stream?->name ?? 'Radio en línea' }}</strong>
            <small>{{ $stream?->url ? 'Señal principal' : 'Señal pendiente de configuración' }}</small>
        </div>

        <button
            class="player-button"
            type="button"
            data-player-toggle
            @disabled(!$stream?->url)
            aria-label="{{ $stream?->url ? 'Reproducir radio' : 'Señal no configurada' }}"
        >
            <span data-player-icon>▶</span>
        </button>

        <div class="player-status">
            <span data-player-status>{{ $stream?->url ? 'Presiona para escuchar' : 'Configura la URL desde administración' }}</span>
            <a href="{{ route('schedule') }}">Ver programación</a>
        </div>

        @if ($stream?->url)
            <audio data-player-audio preload="none" src="{{ $stream->url }}"></audio>
        @endif
    </div>
</aside>

