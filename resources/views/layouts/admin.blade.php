<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Dashboard') — Estación Radial</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="{{ route('admin.dashboard') }}">
            <span aria-hidden="true">▥</span>
            <span>ESTACIÓN <strong>RADIAL</strong></span>
        </a>

        <nav class="admin-nav" aria-label="Administración">
            <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">
                <span aria-hidden="true">⌂</span> Resumen
            </a>
            @if (auth()->user()->hasPermission('news.view'))
                <a class="{{ request()->routeIs('admin.posts.*') ? 'is-active' : '' }}" href="{{ route('admin.posts.index') }}">
                    <span aria-hidden="true">▤</span> Noticias
                </a>
            @endif
            @if (auth()->user()->hasPermission('media.manage'))
                <a class="{{ request()->routeIs('admin.media.*') ? 'is-active' : '' }}" href="{{ route('admin.media.index') }}">
                    <span aria-hidden="true">▧</span> Multimedia
                </a>
            @endif
            @if (auth()->user()->hasPermission('users.view'))
                <a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">
                    <span aria-hidden="true">♙</span> Usuarios
                </a>
            @endif
            <span class="admin-nav__section">Sistema</span>
            <a href="{{ route('home') }}" target="_blank"><span aria-hidden="true">↗</span> Ver portal</a>
            <a class="{{ request()->routeIs('admin.password.*') ? 'is-active' : '' }}" href="{{ route('admin.password.change') }}">
                <span aria-hidden="true">⚿</span> Seguridad
            </a>
        </nav>
    </aside>

    <div class="admin-workspace">
        <header class="admin-topbar">
            <div>
                <span class="eyebrow">@yield('eyebrow', 'Administración')</span>
                <h1>@yield('heading', 'Resumen')</h1>
            </div>
            <div class="admin-account">
                <span>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->roles->pluck('name')->join(', ') }}</small>
                </span>
                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="button button--quiet" type="submit">Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif
            @if (session('warning'))
                <div class="alert alert--warning" role="alert">{{ session('warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--error" role="alert">
                    <strong>Revisa la información:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
