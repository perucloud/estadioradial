<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') — Estación Radial</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite('resources/css/admin.css')
</head>
<body class="auth-body">
    <main class="auth-shell">
        <a class="auth-brand" href="{{ route('home') }}" aria-label="Ir al portal público">
            <span class="auth-brand__bars" aria-hidden="true">▥</span>
            <span>ESTACIÓN<br><strong>RADIAL</strong></span>
        </a>

        <section class="auth-card">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            @yield('content')
        </section>

        <p class="auth-footer">Panel privado · Conexión protegida</p>
    </main>
</body>
</html>
