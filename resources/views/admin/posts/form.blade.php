@extends('layouts.admin')

@php
    $editing = $post !== null;
    $selectedMedia = old('media_id', $post?->media_id);
    $selectedMediaItem = $mediaItems->firstWhere('id', (int) $selectedMedia);
    $selectedTagNames = old('tag_names', $post?->tags->pluck('name')->implode(', ') ?? '');
    $inlineMediaIds = old('inline_media_ids', $post?->inlineMedia->pluck('id')->join(',') ?? '');
    $publicationDate = old(
        'scheduled_for',
        $post?->scheduled_for?->format('Y-m-d\TH:i')
            ?? $post?->published_at?->format('Y-m-d\TH:i')
            ?? now()->format('Y-m-d\TH:i'),
    );
    $autoPublicationDate = old('scheduled_for') === null
        && (! $editing || in_array($post?->status, ['draft', 'in_review'], true));
@endphp

@section('title', $editing ? 'Editar noticia' : 'Nueva noticia')
@section('eyebrow', 'Redacción')
@section('heading', $editing ? 'Editar noticia' : 'Nueva noticia')

@section('content')
    <form
        method="post"
        action="{{ $editing ? route('admin.posts.update', $post) : route('admin.posts.store') }}"
        class="post-editor-form"
    >
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="post-editor-main">
            <section class="panel form-stack">
                <label>
                    Título
                    <input type="text" name="title" value="{{ old('title', $post?->title) }}" maxlength="180" data-slug-source data-seo-title-source required autofocus>
                    @error('title') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Slug
                    <input type="text" name="slug" value="{{ old('slug', $post?->slug) }}" maxlength="200" data-slug-target placeholder="se-genera-desde-el-titulo">
                    @error('slug') <small class="field-error">{{ $message }}</small> @enderror
                </label>
            </section>

            <section class="panel ckeditor-wrapper" data-ckeditor data-draft-key="{{ $post?->id ?? 'new' }}" wire:ignore>
                <div class="ckeditor-editor-header">
                    <div>
                        <span class="eyebrow">Contenido</span>
                        <strong>CKEditor 5</strong>
                    </div>
                    <div>
                        <button
                            class="button button--quiet button--compact"
                            type="button"
                            data-open-media-picker
                            data-media-picker-mode="inline"
                        >
                            <span aria-hidden="true">▧</span> Biblioteca Media
                        </button>
                        <span data-ckeditor-character-count>0 caracteres</span>
                    </div>
                </div>
                <textarea name="body" data-ckeditor-input hidden>{{ old('body', $post?->body) }}</textarea>
                <div class="ckeditor-surface" data-ckeditor-surface></div>
                <div class="ckeditor-status">
                    <span data-autosave-status>Copia local activa</span>
                    <span data-ckeditor-word-count>0 palabras</span>
                </div>
                @error('body') <small class="field-error">{{ $message }}</small> @enderror
            </section>

            <section class="panel form-stack excerpt-panel">
                <div class="panel__header">
                    <div>
                        <span class="eyebrow">Vista pública</span>
                        <h2>Resumen de la noticia</h2>
                    </div>
                    <span class="excerpt-counter" data-excerpt-counter>0 / 500</span>
                </div>
                <label>
                    Resumen
                    <textarea
                        name="excerpt"
                        rows="4"
                        maxlength="500"
                        required
                        data-excerpt-input
                        data-excerpt-generated="{{ old('excerpt', $post?->excerpt) ? 'false' : 'true' }}"
                    >{{ old('excerpt', $post?->excerpt) }}</textarea>
                    <small class="field-help" data-excerpt-help>
                        Se genera automáticamente desde el contenido. Puedes modificarlo manualmente.
                    </small>
                    @error('excerpt') <small class="field-error">{{ $message }}</small> @enderror
                </label>
            </section>

            <section class="panel form-stack">
                <div class="panel__header">
                    <div><span class="eyebrow">Procedencia</span><h2>Fuente y SEO</h2></div>
                </div>
                <div class="form-grid">
                    <label>Nombre de la fuente <input type="text" name="source_name" value="{{ old('source_name', $post?->source_name) }}"></label>
                    <label>URL de la fuente <input type="url" name="source_url" value="{{ old('source_url', $post?->source_url) }}"></label>
                </div>
                <label>
                    Título SEO
                    <input
                        type="text"
                        name="seo_title"
                        value="{{ old('seo_title', $post?->seo_title) }}"
                        maxlength="70"
                        data-seo-title-input
                        data-seo-generated="{{ old('seo_title', $post?->seo_title) ? 'false' : 'true' }}"
                    >
                    <small class="field-help">Se completa desde el título y puedes editarlo.</small>
                </label>
                <label>
                    Descripción SEO
                    <textarea
                        name="seo_description"
                        rows="3"
                        maxlength="170"
                        data-seo-description-input
                        data-seo-generated="{{ old('seo_description', $post?->seo_description) ? 'false' : 'true' }}"
                    >{{ old('seo_description', $post?->seo_description) }}</textarea>
                    <small class="field-help">Se completa desde el resumen y puedes editarla.</small>
                </label>
            </section>
        </div>

        <aside class="post-editor-sidebar">
            <section class="panel form-stack">
                <div>
                    <span class="eyebrow">Publicación</span>
                    <h2>{{ $editing ? ucfirst(str_replace('_', ' ', $post->status)) : 'Borrador nuevo' }}</h2>
                </div>
                <label>
                    Categoría
                    <select name="category_id" data-tag-category required>
                        <option value="">Seleccionar</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $post?->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Fecha y hora de publicación
                    <input
                        type="datetime-local"
                        name="scheduled_for"
                        value="{{ $publicationDate }}"
                        data-publication-datetime
                        data-auto-datetime="{{ $autoPublicationDate ? 'true' : 'false' }}"
                    >
                    <small class="field-help">
                        “Publicar ahora” registra la hora real. Para programar, selecciona una fecha futura.
                    </small>
                </label>
                <div class="publish-actions">
                    <button class="button button--quiet" type="submit" name="intent" value="{{ $editing ? 'preserve' : 'draft' }}">
                        {{ $editing ? 'Guardar cambios' : 'Guardar borrador' }}
                    </button>
                    <button class="button button--quiet" type="submit" name="intent" value="review">Enviar a revisión</button>
                    @if (auth()->user()->hasPermission('news.publish'))
                        <button class="button button--primary" type="submit" name="intent" value="publish">Publicar ahora</button>
                        <button class="button button--quiet" type="submit" name="intent" value="schedule">Programar</button>
                    @endif
                </div>
            </section>

            <section class="panel featured-media-panel">
                <div class="panel__header">
                    <div>
                        <span class="eyebrow">Portada</span>
                        <h2>Imagen destacada</h2>
                    </div>
                </div>
                <input
                    type="hidden"
                    name="media_id"
                    value="{{ $selectedMedia }}"
                    data-featured-media-input
                >
                <div
                    class="featured-media-preview {{ $selectedMediaItem ? 'has-image' : '' }}"
                    data-featured-media-preview
                >
                    <div class="featured-media-preview__placeholder" data-featured-media-placeholder @if ($selectedMediaItem) hidden @endif>
                        <span aria-hidden="true">▧</span>
                        <strong>Selecciona la imagen de portada</strong>
                        <small>Se utilizará en la noticia, las tarjetas y los módulos destacados.</small>
                    </div>
                    <img
                        src="{{ $selectedMediaItem?->url('thumb') ?? '' }}"
                        alt="{{ $selectedMediaItem?->alt_text ?? '' }}"
                        data-featured-media-image
                        @if (! $selectedMediaItem) hidden @endif
                    >
                    <div class="featured-media-preview__caption" data-featured-media-caption @if (! $selectedMediaItem) hidden @endif>
                        <strong data-featured-media-name>{{ $selectedMediaItem?->original_name }}</strong>
                        <small data-featured-media-alt>{{ $selectedMediaItem?->alt_text }}</small>
                    </div>
                </div>
                <button
                    class="button button--primary button--wide"
                    type="button"
                    data-open-media-picker
                    data-media-picker-mode="featured"
                >
                    <span aria-hidden="true">▧</span>
                    <span data-featured-media-action>
                        {{ $selectedMediaItem ? 'Cambiar imagen destacada' : 'Seleccionar imagen destacada' }}
                    </span>
                </button>
                @error('media_id') <small class="field-error">{{ $message }}</small> @enderror
            </section>

            <section class="panel tag-editor" data-tag-editor>
                <span class="eyebrow">Clasificación</span>
                <h2>Etiquetas</h2>
                <label>
                    <span class="sr-only">Etiquetas separadas por comas</span>
                    <textarea
                        name="tag_names"
                        rows="3"
                        maxlength="1500"
                        data-tag-input
                        placeholder="Ejemplo: seguridad ciudadana, Ministerio del Interior, Lima"
                    >{{ $selectedTagNames }}</textarea>
                </label>
                <small class="field-help">Escribe libremente las etiquetas y sepáralas con comas.</small>
                <div class="tag-suggestions">
                    <strong>Sugerencias según el contenido</strong>
                    <div class="tag-suggestion-list" data-tag-suggestions></div>
                </div>
                <script type="application/json" data-tag-source>@json($tags->pluck('name')->values())</script>
                @error('tag_names') <small class="field-error">{{ $message }}</small> @enderror
            </section>

            @if ($editing)
                <section class="panel post-secondary-actions">
                    <a class="button button--quiet" href="{{ route('admin.posts.preview', $post) }}" target="_blank">Vista previa</a>
                    <button class="button button--quiet" type="submit" form="duplicate-post-form">Duplicar</button>
                    @if ($post->status === 'archived')
                        <button class="button button--quiet" type="submit" form="restore-post-form">Recuperar</button>
                    @else
                        <button class="danger-link" type="submit" form="archive-post-form">Archivar</button>
                    @endif
                </section>
            @endif
        </aside>

        <input type="hidden" name="inline_media_ids" value="{{ $inlineMediaIds }}">
    </form>

    @if ($editing)
        <form id="duplicate-post-form" method="post" action="{{ route('admin.posts.duplicate', $post) }}">@csrf</form>
        @if ($post->status === 'archived')
            <form id="restore-post-form" method="post" action="{{ route('admin.posts.restore', $post) }}">@csrf</form>
        @else
            <form id="archive-post-form" method="post" action="{{ route('admin.posts.archive', $post) }}">@csrf</form>
        @endif
    @endif

    <dialog
        class="media-dialog"
        data-media-picker
        data-library-url="{{ route('admin.media.library') }}"
        data-upload-url="{{ route('admin.media.store') }}"
    >
        <div class="media-dialog__header">
            <div>
                <span class="eyebrow">Biblioteca Media</span>
                <h2 data-media-picker-title>Seleccionar imagen</h2>
            </div>
            <button type="button" data-media-picker-close aria-label="Cerrar">×</button>
        </div>
        <div class="media-dialog__toolbar">
            <form data-media-picker-search>
                <label>
                    <span class="sr-only">Buscar imágenes</span>
                    <input type="search" name="q" placeholder="Buscar por nombre, descripción o crédito">
                </label>
                <button class="button button--quiet" type="submit">Buscar</button>
            </form>
            <div>
                <button class="button button--primary" type="button" data-media-picker-upload-toggle>
                    + Añadir nueva imagen
                </button>
                <button class="button button--quiet" type="button" data-media-picker-refresh>
                    ↻ Actualizar
                </button>
            </div>
        </div>
        <form
            class="media-dialog__upload"
            method="post"
            action="{{ route('admin.media.store') }}"
            enctype="multipart/form-data"
            data-media-picker-upload
            hidden
        >
            @csrf
            <div class="media-dialog__upload-heading">
                <div>
                    <span class="eyebrow">Nueva imagen</span>
                    <strong>Añadir sin salir de la noticia</strong>
                </div>
                <button type="button" data-media-picker-upload-close aria-label="Cerrar formulario de carga">×</button>
            </div>
            <div class="media-dialog__upload-grid">
                <label class="media-dialog__file">
                    <span>Archivo de imagen</span>
                    <input type="file" name="files[]" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" required>
                    <small>JPG, PNG, WebP o GIF. Máximo 8 MB.</small>
                </label>
                <label>
                    <span>Texto alternativo <small>(opcional)</small></span>
                    <input type="text" name="alt_texts[]" maxlength="255" placeholder="Si se omite, se generará desde el nombre del archivo">
                </label>
                <label>
                    <span>Pie de foto</span>
                    <input type="text" name="caption" maxlength="255" placeholder="Opcional">
                </label>
                <label>
                    <span>Crédito o autor</span>
                    <input type="text" name="credit" maxlength="255" placeholder="Opcional">
                </label>
            </div>
            <div class="media-dialog__upload-actions">
                <span data-media-picker-upload-status aria-live="polite">
                    La carga comenzará automáticamente al seleccionar el archivo.
                </span>
            </div>
        </form>
        <div class="media-dialog__status" data-media-picker-status aria-live="polite">
            Cargando biblioteca…
        </div>
        <div class="media-dialog__grid" data-media-picker-grid></div>
        <div class="media-dialog__load-more">
            <button class="button button--quiet" type="button" data-media-picker-more hidden>
                Cargar más imágenes
            </button>
        </div>
        <div class="media-dialog__footer">
            <div class="media-dialog__selection">
                <span class="eyebrow">Selección</span>
                <strong data-media-picker-selection>Ninguna imagen seleccionada</strong>
                <div class="media-dialog__url">
                    <label class="sr-only" for="selected-media-url">URL de la imagen seleccionada</label>
                    <input id="selected-media-url" type="text" data-media-picker-url readonly placeholder="Selecciona una imagen para ver su URL">
                    <button class="button button--quiet" type="button" data-media-picker-copy disabled>
                        Copiar URL
                    </button>
                </div>
            </div>
            <div>
                <button class="button button--quiet" type="button" data-media-picker-cancel>Cancelar</button>
                <button class="button button--primary" type="button" data-media-picker-apply disabled>
                    Usar imagen
                </button>
            </div>
        </div>
    </dialog>
@endsection
