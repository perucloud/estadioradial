@props(['categories'])

<div class="network-bar">
    <div class="container network-bar__inner">
        <span>Estación Radial</span>
        <nav aria-label="Enlaces superiores">
            <a href="{{ route('posts.index') }}">Noticias</a>
            <a href="{{ route('schedule') }}">Programación</a>
            <a href="{{ route('live') }}">Ahora en vivo</a>
        </nav>
    </div>
</div>

<header class="site-header">
    <div class="container site-header__main">
        <a class="brand" href="{{ route('home') }}" aria-label="Estación Radial, inicio">
            <span class="brand__signal" aria-hidden="true">
                <i></i><i></i><i></i>
            </span>
            <span class="brand__name">estación<br><strong>radial</strong></span>
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-navigation">
            <span class="sr-only">Abrir menú</span>
            <span></span><span></span><span></span>
        </button>

        <nav id="main-navigation" class="main-nav" aria-label="Navegación principal">
            <a class="{{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Inicio</a>
            <a class="{{ request()->routeIs('live') ? 'is-active' : '' }}" href="{{ route('live') }}">En vivo</a>
            <a class="{{ request()->routeIs('programs.*') ? 'is-active' : '' }}" href="{{ route('programs.index') }}">Programas</a>
            <a class="{{ request()->routeIs('schedule') ? 'is-active' : '' }}" href="{{ route('schedule') }}">Horario</a>
            <a class="{{ request()->routeIs('posts.*') ? 'is-active' : '' }}" href="{{ route('posts.index') }}">Noticias</a>
        </nav>
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

