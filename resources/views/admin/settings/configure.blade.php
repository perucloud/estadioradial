@extends('layouts.admin')

@section('title', 'Configuración del portal')
@section('eyebrow', 'Identidad pública')
@section('heading', 'Configurar portal')

@section('content')
@php
    $tabs = [
        'identity' => ['Identidad', 'Logo, nombre, slogan y frecuencia'],
        'contact' => ['Contacto', 'Dirección y canales directos'],
        'social' => ['Redes sociales', 'Perfiles oficiales'],
        'seo' => ['SEO', 'Buscadores y redes'],
    ];
@endphp

<nav class="settings-tabs settings-tabs--configure" aria-label="Secciones de configuración">
    @foreach ($tabs as $key => [$label, $description])
        <a class="{{ $section === $key ? 'is-active' : '' }}" href="{{ route('admin.settings.configure', $key) }}">
            <strong>{{ $label }}</strong><small>{{ $description }}</small>
        </a>
    @endforeach
</nav>

<form method="post" action="{{ route('admin.settings.configure.update', $section) }}" class="panel settings-form">
    @csrf @method('PUT')

    @if ($section === 'identity')
        <div class="panel__header"><div><span class="eyebrow">Marca</span><h2>Identidad de la emisora</h2></div></div>
        <div class="settings-grid">
            <label id="nombre">Nombre de radio
                <input name="name" value="{{ old('name', $identity['name']) }}" required maxlength="100">
            </label>
            <label id="slogan">Slogan
                <input name="slogan" value="{{ old('slogan', $identity['slogan']) }}" maxlength="160">
            </label>
            <label id="frecuencia">Frecuencia
                <input name="frequency" value="{{ old('frequency', $identity['frequency']) }}" placeholder="Ej. 99.3 FM" maxlength="50">
            </label>
        </div>
        <section id="logo" class="settings-subsection">
            <div><span class="eyebrow">Logo</span><h3>Seleccionar desde Media</h3><p>Elige una imagen reciente o conserva el logotipo tipográfico.</p></div>
            <div class="settings-media-grid">
                <label class="settings-media-card">
                    <input type="radio" name="logo_media_id" value="" @checked(empty($identity['logo_media_id']))>
                    <span class="settings-media-card__empty">ER</span><strong>Logo predeterminado</strong>
                </label>
                @foreach ($mediaItems as $media)
                    <label class="settings-media-card">
                        <input type="radio" name="logo_media_id" value="{{ $media->id }}" @checked((string) old('logo_media_id', $identity['logo_media_id']) === (string) $media->id)>
                        <img src="{{ $media->url('thumb') }}" alt="{{ $media->alt_text ?: $media->original_name }}">
                        <strong>{{ \Illuminate\Support\Str::limit($media->original_name, 24) }}</strong>
                    </label>
                @endforeach
            </div>
        </section>
    @elseif ($section === 'contact')
        <div class="panel__header"><div><span class="eyebrow">Atención</span><h2>Datos de contacto</h2></div></div>
        <div class="settings-grid">
            <label id="direccion">Dirección <textarea name="address" rows="3" maxlength="500">{{ old('address', $contact['address']) }}</textarea></label>
            <label id="telefono">Teléfono <input name="phone" value="{{ old('phone', $contact['phone']) }}" maxlength="50"></label>
            <label id="whatsapp">WhatsApp <input name="whatsapp" value="{{ old('whatsapp', $contact['whatsapp']) }}" maxlength="50"></label>
            <label id="correo">Correo <input type="email" name="email" value="{{ old('email', $contact['email']) }}" maxlength="255"></label>
        </div>
    @elseif ($section === 'social')
        <div class="panel__header"><div><span class="eyebrow">Comunidad</span><h2 id="redes">Redes sociales oficiales</h2></div></div>
        <div class="settings-grid">
            @foreach (['facebook' => 'Facebook', 'x' => 'X', 'tiktok' => 'TikTok', 'instagram' => 'Instagram', 'youtube' => 'YouTube'] as $key => $label)
                <label>{{ $label }} <input type="url" name="{{ $key }}" value="{{ old($key, $social[$key] ?? '') }}" placeholder="https://"></label>
            @endforeach
        </div>
    @elseif ($section === 'colors')
        <div class="panel__header"><div><span class="eyebrow">Diseño</span><h2 id="colores">Colores del sitio</h2></div></div>
        <div class="settings-color-grid">
            @foreach (['primary' => 'Principal', 'secondary' => 'Secundario', 'accent' => 'Acento', 'surface' => 'Superficie', 'text' => 'Texto'] as $key => $label)
                <label><span>{{ $label }}</span><input type="color" name="{{ $key }}" value="{{ old($key, $theme[$key]) }}"><code>{{ $theme[$key] }}</code></label>
            @endforeach
        </div>
        <div class="settings-preview" style="--preview-primary: {{ $theme['primary'] }}; --preview-secondary: {{ $theme['secondary'] }}; --preview-accent: {{ $theme['accent'] }};">
            <span>Vista previa</span><strong>Estación Radial</strong><button type="button">Escuchar radio</button>
        </div>
    @else
        <div class="panel__header"><div><span class="eyebrow">Visibilidad</span><h2 id="seo">SEO general</h2></div></div>
        <div class="settings-grid">
            <label>Título SEO <input name="title" value="{{ old('title', $seo['title']) }}" maxlength="70" required></label>
            <label class="settings-grid__wide">Descripción SEO <textarea name="description" rows="4" maxlength="170" required>{{ old('description', $seo['description']) }}</textarea></label>
            <label>Palabras clave <input name="keywords" value="{{ old('keywords', $seo['keywords']) }}" maxlength="500"></label>
            <label>URL canónica <input type="url" name="canonical_url" value="{{ old('canonical_url', $seo['canonical_url']) }}" placeholder="https://"></label>
            <label class="check-row"><input type="checkbox" name="robots_index" value="1" @checked(old('robots_index', $seo['robots_index']))><span>Permitir que buscadores indexen el portal</span></label>
        </div>
        <section class="settings-subsection">
            <div><span class="eyebrow">Open Graph</span><h3>Imagen para compartir</h3></div>
            <div class="settings-media-grid">
                @foreach ($mediaItems as $media)
                    <label class="settings-media-card">
                        <input type="radio" name="og_media_id" value="{{ $media->id }}" @checked((string) old('og_media_id', $seo['og_media_id']) === (string) $media->id)>
                        <img src="{{ $media->url('thumb') }}" alt="{{ $media->alt_text ?: $media->original_name }}">
                        <strong>{{ \Illuminate\Support\Str::limit($media->original_name, 24) }}</strong>
                    </label>
                @endforeach
            </div>
        </section>
    @endif

    <footer class="settings-actions">
        <a class="button button--quiet" href="{{ route('home') }}" target="_blank" rel="noopener">Ver portal</a>
        <button class="button button--primary" type="submit">Guardar configuración</button>
    </footer>
</form>
@endsection
