@props([
    'email',
    'surface' => 'menu',
])

<nav
    {{ $attributes->class(['utility-access', "utility-access--{$surface}"]) }}
    aria-label="Contacto y administración"
>
    <span class="utility-access__eyebrow">Accesos directos</span>

    <div class="utility-access__links">
        <a class="utility-access__link" href="mailto:{{ $email }}">
            <span class="utility-access__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                    <path d="m4 7 8 6 8-6"/>
                </svg>
            </span>
            <span class="utility-access__text">
                <small>Escríbenos</small>
                <strong>Correo electrónico</strong>
            </span>
        </a>

        <a class="utility-access__link" href="{{ route('admin.dashboard') }}">
            <span class="utility-access__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
            </span>
            <span class="utility-access__text">
                <small>Administración</small>
                <strong>Acceder al dashboard</strong>
            </span>
        </a>
    </div>
</nav>
