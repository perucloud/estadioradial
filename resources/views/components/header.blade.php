@props(['categories', 'socialLinks'])

<header class="site-header">
    <div class="container site-header__main">
        <a class="brand" href="{{ route('home') }}" aria-label="Estación Radial, inicio">
            <span class="brand__signal" aria-hidden="true">
                <i></i><i></i><i></i>
            </span>
            <span class="brand__name">estación<br><strong>radial</strong></span>
        </a>

        <nav id="main-navigation" class="main-nav" aria-label="Navegación principal">
            <a class="{{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Inicio</a>
            <a class="{{ request()->routeIs('live') ? 'is-active' : '' }}" href="{{ route('live') }}">En vivo</a>
            <a class="{{ request()->routeIs('programs.*') ? 'is-active' : '' }}" href="{{ route('programs.index') }}">Programas</a>
            <a class="{{ request()->routeIs('schedule') ? 'is-active' : '' }}" href="{{ route('schedule') }}">Horario</a>
            <a class="{{ request()->routeIs('posts.*') ? 'is-active' : '' }}" href="{{ route('posts.index') }}">Noticias</a>
        </nav>

        <div class="header-tools">
            <nav class="social-links" aria-label="Redes sociales">
                <a href="{{ $socialLinks['facebook'] }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M14 8h3V4h-3c-3 0-5 2-5 5v2H6v4h3v7h4v-7h3l1-4h-4V9c0-.7.3-1 1-1z"/></svg>
                </a>
                <a href="{{ $socialLinks['x'] }}" target="_blank" rel="noopener noreferrer" aria-label="X">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 4l14 16M19 4L5 20"/></svg>
                </a>
                <a href="{{ $socialLinks['tiktok'] }}" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M15 3c.4 2.5 1.8 4 4 4.4v3.3a8 8 0 0 1-4-1.3v6.1A5.5 5.5 0 1 1 10 10v3.4a2.3 2.3 0 1 0 1.7 2.2V3z"/></svg>
                </a>
                <a href="{{ $socialLinks['instagram'] }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.7" r="1"/></svg>
                </a>
                <a href="{{ $socialLinks['youtube'] }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M21 8.2a3 3 0 0 0-2.1-2.1C17 5.6 12 5.6 12 5.6s-5 0-6.9.5A3 3 0 0 0 3 8.2a20 20 0 0 0-.5 3.8 20 20 0 0 0 .5 3.8 3 3 0 0 0 2.1 2.1c1.9.5 6.9.5 6.9.5s5 0 6.9-.5a3 3 0 0 0 2.1-2.1 20 20 0 0 0 .5-3.8 20 20 0 0 0-.5-3.8z"/><path class="social-links__play" d="M10 9l5 3-5 3z"/></svg>
                </a>
            </nav>

            <button
                class="header-action search-toggle"
                type="button"
                aria-expanded="false"
                aria-controls="header-search"
                aria-label="Abrir búsqueda"
            >
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5"/></svg>
            </button>

            <button
                class="header-action menu-toggle"
                type="button"
                aria-expanded="false"
                aria-controls="header-menu-panel"
                aria-label="Abrir menú"
            >
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <div id="header-search" class="header-panel search-panel" hidden>
        <div class="container">
            <form action="{{ route('posts.index') }}" method="get" role="search">
                <label class="sr-only" for="site-search">Buscar noticias</label>
                <input
                    id="site-search"
                    name="q"
                    type="search"
                    value="{{ request('q') }}"
                    placeholder="Buscar noticias..."
                    autocomplete="off"
                >
                <button type="submit">Buscar</button>
            </form>
        </div>
    </div>

    <div id="header-menu-panel" class="header-panel menu-panel" hidden>
        <div class="container menu-panel__grid">
            <nav aria-label="Accesos del menú">
                <span>Explorar</span>
                <a href="{{ route('home') }}">Inicio</a>
                <a href="{{ route('live') }}">En vivo</a>
                <a href="{{ route('programs.index') }}">Programas</a>
                <a href="{{ route('schedule') }}">Programación</a>
            </nav>
            <nav aria-label="Categorías de noticias">
                <span>Categorías</span>
                @foreach ($categories as $category)
                    <a href="{{ route('posts.category', $category) }}">{{ $category->name }}</a>
                @endforeach
            </nav>
        </div>
    </div>

    <div class="live-bar">
        <div class="container live-bar__inner">
            <p><span class="live-dot"></span> Señal disponible las 24 horas</p>
            <div>
                <a class="button button--light" href="{{ route('live') }}">Escuchar radio</a>
                <a class="button button--outline-light" href="{{ route('live') }}#video">Ver video</a>
            </div>
        </div>
    </div>
</header>
