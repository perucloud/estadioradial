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
            <div><span class="eyebrow">Logo</span><h3>Identidad visual</h3><p>Selecciona un archivo de Media o añade un logo nuevo desde tu ordenador.</p></div>
            <input type="hidden" name="logo_media_id" value="{{ old('logo_media_id', $identity['logo_media_id']) }}" data-logo-media-input>
            <div class="settings-logo-picker" data-logo-media-preview>
                <div class="settings-logo-picker__preview {{ $identityLogo ? 'has-image' : '' }}">
                    <img
                        src="{{ $identityLogo?->url('thumb') ?? '' }}"
                        alt="{{ $identityLogo?->alt_text ?? '' }}"
                        data-logo-media-image
                        @if(!$identityLogo) hidden @endif
                    >
                    <span data-logo-media-placeholder @if($identityLogo) hidden @endif>ER</span>
                </div>
                <div class="settings-logo-picker__content">
                    <span class="eyebrow">Logo seleccionado</span>
                    <strong data-logo-media-name>{{ $identityLogo?->original_name ?? 'Logo predeterminado' }}</strong>
                    <small data-logo-media-alt>{{ $identityLogo?->alt_text ?? 'Se utilizará la identidad tipográfica del portal.' }}</small>
                    <div class="settings-logo-picker__actions">
                        <button
                            class="button button--primary settings-logo-picker__button"
                            type="button"
                            data-open-media-picker
                            data-media-picker-mode="logo"
                            style="--genie-color:#153fab"
                        >
                            Elegir logo desde Media
                        </button>
                        <button class="button button--quiet" type="button" data-remove-logo @if(!$identityLogo) hidden @endif>Usar logo predeterminado</button>
                    </div>
                </div>
            </div>
            @error('logo_media_id') <small class="field-error">{{ $message }}</small> @enderror
        </section>
        <section id="favicon" class="settings-subsection">
            <div>
                <span class="eyebrow">Favicon</span>
                <h3>Icono del portal</h3>
                <p>Selecciona desde Media o sube una imagen cuadrada. Se mostrará en la pestaña del navegador del portal público.</p>
            </div>
            <input type="hidden" name="favicon_media_id" value="{{ old('favicon_media_id', $identity['favicon_media_id'] ?? null) }}" data-favicon-media-input>
            <div class="settings-logo-picker settings-favicon-picker" data-favicon-media-preview>
                <div class="settings-logo-picker__preview settings-favicon-picker__preview {{ $identityFavicon ? 'has-image' : '' }}">
                    <img
                        src="{{ $identityFavicon?->url('thumb') ?? '' }}"
                        alt="{{ $identityFavicon?->alt_text ?? '' }}"
                        data-favicon-media-image
                        @if(!$identityFavicon) hidden @endif
                    >
                    <span data-favicon-media-placeholder @if($identityFavicon) hidden @endif>ER</span>
                </div>
                <div class="settings-logo-picker__content">
                    <span class="eyebrow">Favicon seleccionado</span>
                    <strong data-favicon-media-name>{{ $identityFavicon?->original_name ?? 'Favicon predeterminado' }}</strong>
                    <small data-favicon-media-alt>{{ $identityFavicon?->alt_text ?? 'Se utilizará el icono predeterminado del portal.' }}</small>
                    <div class="settings-logo-picker__actions">
                        <button
                            class="button button--primary settings-logo-picker__button"
                            type="button"
                            data-open-media-picker
                            data-media-picker-mode="favicon"
                            style="--genie-color:#153fab"
                        >
                            Elegir favicon desde Media
                        </button>
                        <button class="button button--quiet" type="button" data-remove-favicon @if(!$identityFavicon) hidden @endif>Usar favicon predeterminado</button>
                    </div>
                </div>
            </div>
            @error('favicon_media_id') <small class="field-error">{{ $message }}</small> @enderror
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

@if ($section === 'identity')
    <dialog
        class="media-dialog settings-logo-dialog"
        data-media-picker
        data-library-url="{{ route('admin.settings.media.library') }}"
        data-upload-url="{{ route('admin.settings.media.store') }}"
    >
        <div class="media-dialog__header">
            <div><span class="eyebrow">Biblioteca Media</span><h2 data-media-picker-title>Seleccionar logo</h2></div>
            <button type="button" data-media-picker-close aria-label="Cerrar">×</button>
        </div>
        <div class="media-dialog__toolbar">
            <form data-media-picker-search>
                <label><span class="sr-only">Buscar imágenes</span><input type="search" name="q" placeholder="Buscar por nombre, descripción o crédito"></label>
                <button class="button button--quiet" type="submit">Buscar</button>
            </form>
            <div>
                <button class="button button--primary settings-logo-upload-button" type="button" data-media-picker-upload-toggle>Subir logo desde ordenador</button>
                <button class="button button--quiet" type="button" data-media-picker-refresh>↻ Actualizar</button>
            </div>
        </div>
        <form class="media-dialog__upload" method="post" action="{{ route('admin.settings.media.store') }}" enctype="multipart/form-data" data-media-picker-upload hidden>
            @csrf
            <div class="media-dialog__upload-heading">
                <div><span class="eyebrow">Nueva imagen</span><strong>Subir desde el ordenador</strong></div>
                <button type="button" data-media-picker-upload-close aria-label="Cerrar formulario de carga">×</button>
            </div>
            <div class="media-dialog__upload-grid">
                <label class="media-dialog__file">
                    <span>Archivo de imagen</span>
                    <input type="file" name="files[]" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" required>
                    <small>JPG, PNG, WebP o GIF. Máximo 8 MB. La carga es automática.</small>
                </label>
                <label><span>Texto alternativo <small>(opcional)</small></span><input type="text" name="alt_texts[]" maxlength="255" placeholder="Se generará desde el nombre del archivo"></label>
                <label><span>Crédito o autor <small>(opcional)</small></span><input type="text" name="credit" maxlength="255" placeholder="Opcional"></label>
            </div>
            <div class="media-dialog__upload-actions"><span data-media-picker-upload-status aria-live="polite">La carga comenzará automáticamente al seleccionar el archivo.</span></div>
        </form>
        <div class="media-dialog__status" data-media-picker-status aria-live="polite">Cargando biblioteca…</div>
        <div class="media-dialog__grid" data-media-picker-grid></div>
        <div class="media-dialog__load-more"><button class="button button--quiet" type="button" data-media-picker-more hidden>Cargar más imágenes</button></div>
        <div class="media-dialog__footer">
            <div class="media-dialog__selection">
                <span class="eyebrow">Selección</span>
                <strong data-media-picker-selection>Ninguna imagen seleccionada</strong>
                <div class="media-dialog__url">
                    <label class="sr-only" for="selected-logo-url">URL del logo seleccionado</label>
                    <input id="selected-logo-url" type="text" data-media-picker-url readonly placeholder="Selecciona una imagen para ver su URL">
                    <button class="button button--quiet" type="button" data-media-picker-copy disabled>Copiar URL</button>
                </div>
            </div>
            <div>
                <button class="button button--quiet" type="button" data-media-picker-cancel>Cancelar</button>
                <button class="button button--primary" type="button" data-media-picker-apply disabled>Usar como logo</button>
            </div>
        </div>
    </dialog>
@endif
@endsection
