<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#d9251b">
    <meta name="description" content="@yield('description', 'Noticias, programas y radio en vivo desde Estación Radial.')">
    <title>@yield('title', 'Estación Radial')</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <x-header :categories="$navigationCategories" />

    <main id="contenido">
        @yield('content')
    </main>

    <x-footer />
    <x-player :stream="$globalAudioStream" />

    @stack('scripts')
</body>
</html>
