@extends('layouts.admin')

@php
    $editing = $post !== null;
    $selectedMedia = old('media_id', $post?->media_id);
    $selectedTags = old('tag_ids', $post?->tags->pluck('id')->all() ?? []);
    $inlineMediaIds = old('inline_media_ids', $post?->inlineMedia->pluck('id')->join(',') ?? '');
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
                    <input type="text" name="title" value="{{ old('title', $post?->title) }}" maxlength="180" data-slug-source required autofocus>
                    @error('title') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Slug
                    <input type="text" name="slug" value="{{ old('slug', $post?->slug) }}" maxlength="200" data-slug-target placeholder="se-genera-desde-el-titulo">
                    @error('slug') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Resumen
                    <textarea name="excerpt" rows="4" maxlength="500" required>{{ old('excerpt', $post?->excerpt) }}</textarea>
                    @error('excerpt') <small class="field-error">{{ $message }}</small> @enderror
                </label>
            </section>

            <section class="panel tiptap-wrapper" data-tiptap data-draft-key="{{ $post?->id ?? 'new' }}">
                <div class="tiptap-editor-header">
                    <div>
                        <span class="eyebrow">Contenido</span>
                        <strong>Editor de noticia</strong>
                    </div>
                    <div>
                        <button class="button button--quiet button--compact" type="button" data-editor-image>
                            <span aria-hidden="true">▧</span> Biblioteca Media
                        </button>
                        <span data-tiptap-character-count>0 caracteres</span>
                    </div>
                </div>

                <div class="tiptap-toolbar" role="toolbar" aria-label="Formato de la noticia">
                    <div class="tiptap-toolbar__group">
                        <button type="button" data-editor-command="undo" title="Deshacer" aria-label="Deshacer">↶</button>
                        <button type="button" data-editor-command="redo" title="Rehacer" aria-label="Rehacer">↷</button>
                    </div>

                    <label class="tiptap-block-select">
                        <span class="sr-only">Tipo de bloque</span>
                        <select data-editor-block aria-label="Tipo de bloque">
                            <option value="paragraph">Párrafo</option>
                            <option value="heading2">Título H2</option>
                            <option value="heading3">Título H3</option>
                            <option value="heading4">Título H4</option>
                            <option value="codeBlock">Bloque de código</option>
                        </select>
                    </label>

                    <div class="tiptap-toolbar__group">
                        <button type="button" data-editor-command="bold" title="Negrita" aria-label="Negrita"><strong>B</strong></button>
                        <button type="button" data-editor-command="italic" title="Cursiva" aria-label="Cursiva"><em>I</em></button>
                        <button type="button" data-editor-command="underline" title="Subrayado" aria-label="Subrayado"><u>U</u></button>
                        <button type="button" data-editor-command="strike" title="Tachado" aria-label="Tachado"><s>S</s></button>
                    </div>

                    <div class="tiptap-toolbar__group">
                        <label class="tiptap-color-control" title="Color del texto">
                            <span aria-hidden="true">A</span>
                            <input type="color" value="#202226" data-editor-color aria-label="Color del texto">
                        </label>
                        <label class="tiptap-color-control tiptap-color-control--highlight" title="Resaltar texto">
                            <span aria-hidden="true">◆</span>
                            <input type="color" value="#fff2a8" data-editor-highlight aria-label="Color de resaltado">
                        </label>
                    </div>

                    <div class="tiptap-toolbar__group">
                        <button type="button" data-editor-align="left" title="Alinear a la izquierda" aria-label="Alinear a la izquierda">≡</button>
                        <button type="button" data-editor-align="center" title="Centrar" aria-label="Centrar">≡</button>
                        <button type="button" data-editor-align="right" title="Alinear a la derecha" aria-label="Alinear a la derecha">≡</button>
                        <button type="button" data-editor-align="justify" title="Justificar" aria-label="Justificar">≣</button>
                    </div>

                    <details class="tiptap-more">
                        <summary title="Más herramientas" aria-label="Más herramientas">•••</summary>
                        <div class="tiptap-more__panel">
                            <div class="tiptap-more__group">
                                <button type="button" data-editor-command="bulletList" title="Lista con viñetas">☷ <span>Viñetas</span></button>
                                <button type="button" data-editor-command="orderedList" title="Lista numerada">1. <span>Numerada</span></button>
                                <button type="button" data-editor-command="blockquote" title="Cita">❞ <span>Cita</span></button>
                                <button type="button" data-editor-link title="Insertar enlace">🔗 <span>Enlace</span></button>
                                <button type="button" data-editor-image title="Insertar imagen">▧ <span>Media</span></button>
                                <button type="button" data-editor-video title="Insertar video de YouTube">▶ <span>Video</span></button>
                                <button type="button" data-editor-command="table" title="Insertar tabla">▦ <span>Tabla</span></button>
                                <button type="button" data-editor-command="codeBlock" title="Bloque de código">&lt;/&gt; <span>Código</span></button>
                                <button type="button" data-editor-command="horizontalRule" title="Separador">― <span>Separador</span></button>
                            </div>
                            <div class="tiptap-more__group tiptap-more__group--table">
                                <button type="button" data-editor-command="addRowAfter" data-table-command>＋ fila</button>
                                <button type="button" data-editor-command="addColumnAfter" data-table-command>＋ columna</button>
                                <button type="button" data-editor-command="deleteRow" data-table-command>− fila</button>
                                <button type="button" data-editor-command="deleteColumn" data-table-command>− columna</button>
                                <button type="button" data-editor-command="deleteTable" data-table-command>Eliminar tabla</button>
                            </div>
                            <div class="tiptap-more__group">
                                <button type="button" data-editor-clear title="Limpiar formato">Tx <span>Limpiar formato</span></button>
                                <button type="button" data-editor-fullscreen title="Pantalla completa">⛶ <span>Pantalla completa</span></button>
                            </div>
                        </div>
                    </details>
                </div>
                <textarea name="body" data-tiptap-input hidden>{{ old('body', $post?->body) }}</textarea>
                <div class="tiptap-surface" data-tiptap-surface></div>
                <div class="tiptap-status">
                    <span data-autosave-status>Copia local activa</span>
                    <span data-tiptap-count>0 palabras</span>
                </div>
                @error('body') <small class="field-error">{{ $message }}</small> @enderror
            </section>

            <section class="panel form-stack">
                <div class="panel__header">
                    <div><span class="eyebrow">Procedencia</span><h2>Fuente y SEO</h2></div>
                </div>
                <div class="form-grid">
                    <label>Nombre de la fuente <input type="text" name="source_name" value="{{ old('source_name', $post?->source_name) }}"></label>
                    <label>URL de la fuente <input type="url" name="source_url" value="{{ old('source_url', $post?->source_url) }}"></label>
                </div>
                <label>Título SEO <input type="text" name="seo_title" value="{{ old('seo_title', $post?->seo_title) }}" maxlength="70"></label>
                <label>Descripción SEO <textarea name="seo_description" rows="3" maxlength="170">{{ old('seo_description', $post?->seo_description) }}</textarea></label>
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
                    <select name="category_id" required>
                        <option value="">Seleccionar</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $post?->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Programar fecha y hora
                    <input
                        type="datetime-local"
                        name="scheduled_for"
                        value="{{ old('scheduled_for', $post?->scheduled_for?->format('Y-m-d\TH:i')) }}"
                    >
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

            <section class="panel">
                <div class="panel__header">
                    <div><span class="eyebrow">Portada</span><h2>Imagen principal</h2></div>
                    <a class="table-link" href="{{ route('admin.media.index') }}" target="_blank">Biblioteca</a>
                </div>
                <div class="featured-media-grid">
                    @forelse ($mediaItems as $media)
                        <label class="featured-media-option">
                            <input type="radio" name="media_id" value="{{ $media->id }}" @checked((int) $selectedMedia === $media->id) required>
                            <img src="{{ $media->url('thumb') }}" alt="{{ $media->alt_text }}" loading="lazy">
                            <span>{{ Str::limit($media->alt_text, 50) }}</span>
                        </label>
                    @empty
                        <p class="empty-state">Primero sube una imagen a Multimedia.</p>
                    @endforelse
                </div>
                @error('media_id') <small class="field-error">{{ $message }}</small> @enderror
            </section>

            @if ($tags->isNotEmpty())
                <section class="panel">
                    <span class="eyebrow">Clasificación</span>
                    <h2>Etiquetas</h2>
                    <div class="tag-options">
                        @foreach ($tags as $tag)
                            <label class="check-row">
                                <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked(in_array($tag->id, $selectedTags))>
                                <span>{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif

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

    <dialog class="media-dialog" data-media-dialog>
        <div class="media-dialog__header">
            <div><span class="eyebrow">Biblioteca</span><h2>Insertar imagen</h2></div>
            <button type="button" data-dialog-close aria-label="Cerrar">×</button>
        </div>
        <div class="media-dialog__grid">
            @foreach ($mediaItems as $media)
                <button
                    type="button"
                    data-insert-media
                    data-media-id="{{ $media->id }}"
                    data-media-url="{{ $media->url('article') }}"
                    data-media-alt="{{ $media->alt_text }}"
                    data-media-caption="{{ $media->caption }}"
                >
                    <img src="{{ $media->url('thumb') }}" alt="">
                    <span>{{ Str::limit($media->alt_text, 55) }}</span>
                </button>
            @endforeach
        </div>
    </dialog>
@endsection
