@extends('layouts.admin')

@section('title', 'Multimedia')
@section('eyebrow', 'Biblioteca reutilizable')
@section('heading', 'Multimedia')

@section('content')
    <section class="panel media-upload-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Nueva carga</span>
                <h2>Subir imágenes</h2>
            </div>
            <span class="badge">JPG, PNG, WebP o GIF · máximo 8 MB</span>
        </div>
        <form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="form-stack" data-media-upload>
            @csrf
            <label>
                Archivos
                <input type="file" name="files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
            </label>
            <div class="upload-list" data-upload-list>
                <label class="upload-alt-row">
                    Texto alternativo (opcional)
                    <input type="text" name="alt_texts[]" maxlength="255" placeholder="Si se omite, se generará desde el nombre del archivo">
                </label>
            </div>
            <div class="form-grid form-grid--three">
                <label>Pie de foto <input type="text" name="caption" maxlength="255"></label>
                <label>Crédito <input type="text" name="credit" maxlength="255"></label>
                <label>Licencia <input type="text" name="license" maxlength="255"></label>
            </div>
            <button class="button button--primary" type="submit">Añadir a la biblioteca</button>
        </form>
    </section>

    <div class="page-actions">
        <form method="get" class="inline-search">
            <input type="search" name="q" value="{{ $search }}" placeholder="Buscar por nombre, descripción o crédito">
            <button class="button button--quiet" type="submit">Buscar</button>
        </form>
        <span>{{ $mediaItems->total() }} archivo(s)</span>
    </div>

    <section class="media-admin-grid">
        @forelse ($mediaItems as $media)
            <article class="media-admin-card">
                <img src="{{ $media->url('thumb') }}" alt="{{ $media->alt_text }}" loading="lazy">
                <div class="media-admin-card__body">
                    <strong title="{{ $media->original_name }}">{{ Str::limit($media->original_name, 35) }}</strong>
                    <small>{{ $media->width }} × {{ $media->height }} · {{ number_format($media->size / 1024) }} KB</small>
                    <details>
                        <summary>Editar metadatos</summary>
                        <form method="post" action="{{ route('admin.media.update', $media) }}" class="form-stack form-stack--compact">
                            @csrf
                            @method('PUT')
                            <label>Texto alternativo (opcional) <input type="text" name="alt_text" value="{{ $media->alt_text }}"></label>
                            <label>Pie <input type="text" name="caption" value="{{ $media->caption }}"></label>
                            <label>Crédito <input type="text" name="credit" value="{{ $media->credit }}"></label>
                            <label>Licencia <input type="text" name="license" value="{{ $media->license }}"></label>
                            <button class="button button--quiet" type="submit">Guardar</button>
                        </form>
                    </details>
                    <form method="post" action="{{ route('admin.media.destroy', $media) }}" onsubmit="return confirm('¿Retirar esta imagen?')">
                        @csrf
                        @method('DELETE')
                        <button class="danger-link" type="submit" @disabled($media->isInUse())>
                            {{ $media->isInUse() ? 'En uso' : 'Eliminar' }}
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="panel empty-state">No se encontraron imágenes.</div>
        @endforelse
    </section>

    {{ $mediaItems->links() }}
@endsection
