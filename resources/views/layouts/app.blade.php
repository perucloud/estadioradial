<!DOCTYPE html>
<html lang="es">
<head>
    @php
        $metaTitle = trim($__env->yieldContent('seo_title', $__env->yieldContent('title', $siteSeo['title'])));
        $metaDescription = trim($__env->yieldContent('description', $siteSeo['description']));
        $metaImage = trim($__env->yieldContent('seo_image', $siteOgImageUrl ?? ''));
        $metaType = trim($__env->yieldContent('seo_type', 'website'));
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $siteTheme['primary'] }}">
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:locale" content="es_PE">
    <meta property="og:type" content="{{ $metaType }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $siteIdentity['name'] }}">
    @if (!empty($siteSeo['keywords']))<meta name="keywords" content="{{ $siteSeo['keywords'] }}">@endif
    @if (!empty($siteSeo['canonical_url']))<link rel="canonical" href="{{ $siteSeo['canonical_url'] }}">@endif
    @unless ($siteSeo['robots_index'])<meta name="robots" content="noindex,nofollow">@endunless
    <meta name="twitter:card" content="{{ $metaImage !== '' ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @if ($metaImage !== '')
        <meta property="og:image" content="{{ $metaImage }}">
        <meta name="twitter:image" content="{{ $metaImage }}">
    @endif
    <title>@yield('title', $siteIdentity['name'])</title>
    <link rel="icon" href="{{ $siteFaviconUrl ?: asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{--red:{{ $siteTheme['primary'] }};--red-dark:{{ $siteTheme['secondary'] }};--ink:{{ $siteTheme['text'] }};--white:{{ $siteTheme['surface'] }};--brand-accent:{{ $siteTheme['accent'] }};}</style>
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <x-header
        :categories="$navigationCategories"
        :social-links="$socialLinks"
        :contact-email="$contactEmail"
        :identity="$siteIdentity"
        :logo-url="$siteLogoUrl"
    />

    <main id="contenido">
        @yield('content')
    </main>

    <x-footer :contact-email="$contactEmail" :contact="$siteContact" :identity="$siteIdentity" :logo-url="$siteLogoUrl" />
    <x-player :stream="$globalAudioStream" />

    @stack('scripts')
</body>
</html>
