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
                <x-admin-nav-icon name="dashboard2.png" /> Dashboard
            </a>

            @if (auth()->user()->hasPermission('news.view'))
                <details
                    class="admin-nav-group {{ request()->routeIs('admin.posts.*', 'admin.categories.*', 'admin.locations.*', 'admin.tags.*') ? 'is-active' : '' }}"
                    data-admin-nav-group
                >
                    <summary>
                        <x-admin-nav-icon name="lista3.png" />
                        <span>Noticias</span>
                        <span class="admin-nav-group__chevron" aria-hidden="true">›</span>
                    </summary>
                    <div class="admin-nav-flyout">
                        <strong>Noticias</strong>
                        @if (auth()->user()->hasPermission('news.create'))
                            <a class="{{ request()->routeIs('admin.posts.create') ? 'is-active' : '' }}" href="{{ route('admin.posts.create') }}">
                                <x-admin-nav-icon name="añadir.png" /> Crear noticia
                            </a>
                        @endif
                        <a class="{{ request()->routeIs('admin.posts.index') ? 'is-active' : '' }}" href="{{ route('admin.posts.index') }}">
                            <x-admin-nav-icon name="lista.png" /> Todas las noticias
                        </a>
                        @if (auth()->user()->hasPermission('categories.manage'))
                            <a class="{{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}" href="{{ route('admin.categories.index') }}">
                                <x-admin-nav-icon name="carpetas.png" /> Categorías
                            </a>
                            <a class="{{ request()->routeIs('admin.tags.*') ? 'is-active' : '' }}" href="{{ route('admin.tags.index') }}">
                                <x-admin-nav-icon name="etiqueta.png" /> Etiquetas
                            </a>
                        @endif
                        @if (auth()->user()->hasPermission('locations.manage'))
                            <a class="{{ request()->routeIs('admin.locations.*') ? 'is-active' : '' }}" href="{{ route('admin.locations.index') }}">
                                <x-admin-nav-icon name="home.png" /> Ubicaciones
                            </a>
                        @endif
                    </div>
                </details>
            @endif

            @if (auth()->user()->hasPermission('media.manage'))
                <a class="{{ request()->routeIs('admin.media.*') ? 'is-active' : '' }}" href="{{ route('admin.media.index') }}">
                    <x-admin-nav-icon name="galeria.png" /> Media
                </a>
            @endif

              @if (auth()->user()->hasPermission('schedule.manage'))
                  <a class="{{ request()->routeIs('admin.schedule.*') ? 'is-active' : '' }}" href="{{ route('admin.schedule.index') }}">
                      <x-admin-nav-icon name="lista.png" /> Programación radial
                  </a>
              @endif

              @if (auth()->user()->hasPermission('programs.manage'))
                  <a class="{{ request()->routeIs('admin.programs.*') ? 'is-active' : '' }}" href="{{ route('admin.programs.index') }}">
                      <x-admin-nav-icon name="lista2.png" /> Programas
                  </a>
              @endif

              @if (auth()->user()->hasPermission('users.view'))
                  <a class="{{ request('role') === 'locutor' ? 'is-active' : '' }}" href="{{ route('admin.users.index', ['role' => 'locutor']) }}">
                      <x-admin-nav-icon name="group.png" /> Locutores
                  </a>
              @endif

              @if (auth()->user()->hasPermission('stream.manage'))
                  <a class="{{ request()->routeIs('admin.streams.*') ? 'is-active' : '' }}" href="{{ route('admin.streams.index') }}">
                      <x-admin-nav-icon name="audio.png" /> Streaming
                  </a>
              @endif

              @if (auth()->user()->hasPermission('settings.manage'))
                  <a class="{{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" href="{{ route('admin.settings.portal.edit') }}">
                      <x-admin-nav-icon name="configurar.png" /> Configuración del portal
                  </a>
              @endif

              @if (auth()->user()->hasPermission('advertising.manage'))
                  <a class="{{ request()->routeIs('admin.advertisements.*') ? 'is-active' : '' }}" href="{{ route('admin.advertisements.index') }}">
                      <x-admin-nav-icon name="publicidad.gif" /> Publicidad y banners
                  </a>
              @endif

            @if (auth()->user()->hasPermission('appearance.manage'))
                <details
                    class="admin-nav-group {{ request()->routeIs('admin.appearance.*') ? 'is-active' : '' }}"
                    data-admin-nav-group
                >
                    <summary>
                        <x-admin-nav-icon name="color.png" />
                        <span>Apariencia</span>
                        <span class="admin-nav-group__chevron" aria-hidden="true">›</span>
                    </summary>
                    <div class="admin-nav-flyout">
                        <strong>Apariencia</strong>
                        <span class="admin-nav-flyout__disabled">
                            <x-admin-nav-icon name="menu.png" /> Menú <small>Próximamente</small>
                        </span>
                        <a class="{{ request()->routeIs('admin.appearance.homepage.*') ? 'is-active' : '' }}" href="{{ route('admin.appearance.homepage.edit') }}">
                            <x-admin-nav-icon name="portada.png" /> Portada
                        </a>
                    </div>
                </details>
            @endif

            @if (auth()->user()->hasPermission('settings.manage'))
                <details class="admin-nav-group" data-admin-nav-group>
                    <summary>
                        <x-admin-nav-icon name="configurar.png" />
                        <span>Configurar</span>
                        <span class="admin-nav-group__chevron" aria-hidden="true">›</span>
                    </summary>
                    <div class="admin-nav-flyout admin-nav-flyout--long">
                        <strong>Configuración del portal</strong>
                        @foreach ([
                            ['home.png', 'Logo'],
                            ['editar1.png', 'Nombre de radio'],
                            ['etiqueta.png', 'Slogan'],
                            ['audio.png', 'Frecuencia'],
                            ['home.png', 'Dirección'],
                            ['perfil.png', 'Teléfono'],
                            ['whatsapp.png', 'WhatsApp'],
                            ['correo.png', 'Correo'],
                            ['fb.png', 'Redes sociales'],
                            ['color.png', 'Colores del sitio'],
                            ['buscar.png', 'SEO'],
                        ] as [$icon, $label])
                            <span class="admin-nav-flyout__disabled">
                                <x-admin-nav-icon :name="$icon" /> {{ $label }}
                            </span>
                        @endforeach
                    </div>
                </details>

                <details class="admin-nav-group" data-admin-nav-group>
                    <summary>
                        <x-admin-nav-icon name="ajustes.png" />
                        <span>Ajustes</span>
                        <span class="admin-nav-group__chevron" aria-hidden="true">›</span>
                    </summary>
                    <div class="admin-nav-flyout admin-nav-flyout--columns">
                        <strong>Ajustes del sistema</strong>
                        @foreach ([
                            ['etiqueta.png', 'Idioma'],
                            ['calendar.png', 'Zona horaria'],
                            ['calendario.png', 'Formato de fecha'],
                            ['correo.png', 'SMTP (correo)'],
                            ['nube.png', 'Caché'],
                            ['tools.png', 'Mantenimiento'],
                            ['guardar.png', 'Respaldos'],
                            ['bloquear.png', 'Seguridad'],
                        ] as [$icon, $label])
                            <span class="admin-nav-flyout__disabled">
                                <x-admin-nav-icon :name="$icon" /> {{ $label }}
                            </span>
                        @endforeach
                    </div>
                </details>
            @endif

            @if (auth()->user()->hasPermission('users.view'))
                <a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">
                    <x-admin-nav-icon name="group.png" /> Usuarios
                </a>
            @endif

            <span class="admin-nav__section">Sistema</span>
            <a href="{{ route('home') }}" target="_blank" rel="noopener">
                <x-admin-nav-icon name="external.png" /> Ver portal
            </a>
            <a class="{{ request()->routeIs('admin.password.*') ? 'is-active' : '' }}" href="{{ route('admin.password.change') }}">
                <x-admin-nav-icon name="password.png" /> Seguridad
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
