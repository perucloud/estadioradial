@extends('layouts.admin')

@section('title', 'Multimedia')
@section('eyebrow', 'Biblioteca reutilizable')
@section('heading', 'Multimedia')

@section('content')
    <div
        data-media-library
        data-upload-url="{{ route('admin.media.store') }}"
        data-csrf-token="{{ csrf_token() }}"
    >
        <section class="panel media-upload-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Nueva carga</span>
                    <h2>Subir imágenes</h2>
                </div>
                <span class="badge">JPG, PNG, WebP o GIF · máximo 8 MB</span>
            </div>

            <form
                method="post"
                action="{{ route('admin.media.store') }}"
                enctype="multipart/form-data"
                data-media-library-upload
            >
                @csrf
                <label class="media-dropzone" data-media-dropzone>
                    <input
                        type="file"
                        name="files[]"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        multiple
                        required
                        data-media-file-input
                    >
                    <span class="media-dropzone__icon" aria-hidden="true">↥</span>
                    <span class="media-dropzone__copy">
                        <strong>Arrastra tus imágenes aquí</strong>
                        <small>La carga comenzará automáticamente al seleccionar o soltar los archivos.</small>
                    </span>
                    <span class="media-file-button">
                        <span aria-hidden="true">▧</span>
                        Elegir archivos
                    </span>
                </label>

                <div class="media-upload-progress" data-media-upload-progress hidden>
                    <span></span>
                </div>
                <p class="media-upload-status" data-media-upload-status aria-live="polite">
                    Los metadatos son opcionales y podrás completarlos después desde “Editar”.
                </p>
            </form>
        </section>

        <div class="page-actions">
            <form method="get" class="inline-search">
                <input type="search" name="q" value="{{ $search }}" placeholder="Buscar por nombre, descripción o crédito">
                <button class="button button--quiet" type="submit">Buscar</button>
            </form>
            <span data-media-total>{{ $mediaItems->total() }} archivo(s)</span>
        </div>

        <section class="media-admin-grid" data-media-library-grid>
            @forelse ($mediaItems as $media)
                @php($mediaInUse = $media->isInUse())
                <article class="media-admin-card" data-media-card="{{ $media->id }}">
                    <div class="media-admin-card__visual">
                        <img src="{{ $media->url('thumb') }}" alt="{{ $media->alt_text }}" loading="lazy">
                        @if ($mediaInUse)
                            <span class="media-usage-badge">En uso</span>
                        @endif
                    </div>
                    <div class="media-admin-card__body">
                        <strong title="{{ $media->original_name }}">{{ Str::limit($media->original_name, 35) }}</strong>
                        <small>{{ $media->width }} × {{ $media->height }} · {{ number_format($media->size / 1024) }} KB</small>

                        <div class="media-admin-card__actions">
                            <button
                                class="media-card-action media-card-action--edit"
                                type="button"
                                data-media-edit
                                data-update-url="{{ route('admin.media.update', $media) }}"
                                data-media-id="{{ $media->id }}"
                                data-media-name="{{ $media->original_name }}"
                                data-media-thumb="{{ $media->url('thumb') }}"
                                data-media-alt="{{ $media->alt_text }}"
                                data-media-caption="{{ $media->caption }}"
                                data-media-credit="{{ $media->credit }}"
                                data-media-license="{{ $media->license }}"
                            >
                                <span aria-hidden="true">✎</span> Editar
                            </button>
                            <button
                                class="media-card-action media-card-action--copy"
                                type="button"
                                data-media-copy
                                data-media-url="{{ url($media->url('article')) }}"
                            >
                                <span aria-hidden="true">⧉</span> Copiar link
                            </button>
                        </div>

                        <form
                            method="post"
                            action="{{ route('admin.media.destroy', $media) }}"
                            data-confirm-delete="¿Retirar esta imagen?"
                        >
                            @csrf
                            @method('DELETE')
                            <button class="danger-link" type="submit" @disabled($mediaInUse)>
                                {{ $mediaInUse ? 'Protegida porque está en uso' : 'Eliminar imagen' }}
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="panel empty-state" data-media-empty>No se encontraron imágenes.</div>
            @endforelse
        </section>

        {{ $mediaItems->links() }}

        <dialog class="media-metadata-dialog" data-media-metadata-dialog aria-labelledby="media-metadata-title">
            <form method="post" data-media-metadata-form>
                @csrf
                @method('PUT')
                <header class="media-metadata-dialog__header">
                    <div class="media-metadata-dialog__identity">
                        <span class="media-metadata-dialog__icon" aria-hidden="true">✎</span>
                        <div>
                            <span class="eyebrow">Biblioteca Multimedia</span>
                            <h2 id="media-metadata-title">Editar metadatos</h2>
                        </div>
                    </div>
                    <button type="button" data-close-media-metadata aria-label="Cerrar">×</button>
                </header>

                <div class="media-metadata-dialog__body">
                    <aside class="media-metadata-preview">
                        <img src="" alt="" data-media-metadata-image>
                        <strong data-media-metadata-name></strong>
                        <small>Los campos son opcionales y pueden actualizarse cuando sea necesario.</small>
                    </aside>
                    <div class="media-metadata-fields">
                        <label>
                            Texto alternativo
                            <input type="text" name="alt_text" maxlength="255" data-media-metadata-alt placeholder="Descripción accesible opcional">
                        </label>
                        <label>
                            Pie de foto
                            <input type="text" name="caption" maxlength="255" data-media-metadata-caption placeholder="Texto mostrado bajo la imagen">
                        </label>
                        <label>
                            Crédito o autor
                            <input type="text" name="credit" maxlength="255" data-media-metadata-credit placeholder="Fotógrafo, institución o fuente">
                        </label>
                        <label>
                            Licencia
                            <input type="text" name="license" maxlength="255" data-media-metadata-license placeholder="Uso editorial, Creative Commons, etc.">
                        </label>
                        <p class="media-metadata-error" data-media-metadata-error role="alert" hidden></p>
                    </div>
                </div>

                <footer class="media-metadata-dialog__footer">
                    <button class="button button--quiet" type="button" data-close-media-metadata>Cancelar</button>
                    <button class="button button--primary" type="submit" data-save-media-metadata>Guardar metadatos</button>
                </footer>
            </form>
        </dialog>

        <div class="media-toast" data-media-toast role="status" aria-live="polite" hidden></div>
    </div>
@endsection
