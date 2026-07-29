<!DOCTYPE html>
<html lang="es">
<head>
    @php
        $metaTitle = trim($__env->yieldContent('seo_title', $__env->yieldContent('title', 'Estación Radial')));
        $metaDescription = trim($__env->yieldContent('description', 'Noticias, programas y radio en vivo desde Estación Radial.'));
        $metaImage = trim($__env->yieldContent('seo_image'));
        $metaType = trim($__env->yieldContent('seo_type', 'website'));
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#d9251b">
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:locale" content="es_PE">
    <meta property="og:type" content="{{ $metaType }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Estación Radial">
    <meta name="twitter:card" content="{{ $metaImage !== '' ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @if ($metaImage !== '')
        <meta property="og:image" content="{{ $metaImage }}">
        <meta name="twitter:image" content="{{ $metaImage }}">
    @endif
    <title>@yield('title', 'Estación Radial')</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <x-header
        :categories="$navigationCategories"
        :social-links="$socialLinks"
        :contact-email="$contactEmail"
    />

    <main id="contenido">
        @yield('content')
    </main>

    <x-footer :contact-email="$contactEmail" />
    <x-player :stream="$globalAudioStream" />

    @stack('scripts')
</body>
</html>
