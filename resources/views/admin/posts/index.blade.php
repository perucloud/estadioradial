@extends('layouts.admin')

@section('title', 'Noticias')
@section('eyebrow', 'Redacción')
@section('heading', 'Noticias')

@php
    $statusLabels = [
        'draft' => 'Borrador',
        'in_review' => 'En revisión',
        'scheduled' => 'Programada',
        'published' => 'Publicada',
        'archived' => 'Archivada',
        'trash' => 'Papelera',
    ];
@endphp

@section('content')
    <div class="page-actions">
        <p>Crea, revisa, programa y publica contenido sin modificar código.</p>
        @if (auth()->user()->hasPermission('news.create'))
            <a class="button button--primary" href="{{ route('admin.posts.create') }}">Nueva noticia</a>
        @endif
    </div>

    <section class="panel filter-panel">
        <form method="get" class="admin-filters">
            <input type="search" name="q" value="{{ $search }}" placeholder="Buscar título o resumen">
            <select name="status">
                <option value="">Todos los estados</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $statusLabels[$status] }}</option>
                @endforeach
            </select>
            <select name="category">
                <option value="">Todas las categorías</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($selectedCategory === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <label class="per-page-field">
                <span class="sr-only">Noticias por página</span>
                <select name="per_page" aria-label="Noticias por página">
                    @foreach ([10, 20, 50, 100] as $option)
                        <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} por página</option>
                    @endforeach
                </select>
            </label>
            <button class="button button--quiet" type="submit">Filtrar</button>
        </form>
    </section>

    <section class="panel table-panel">
        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Noticia</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Actualización</th>
                        <th><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr>
                            <td>
                                <strong>{{ $post->title }}</strong>
                                <small>{{ $post->creator?->name ?? $post->author }}</small>
                            </td>
                            <td>{{ $post->category->name }}</td>
                            <td>
                                <span class="badge badge--{{ $post->trashed() ? 'trash' : $post->status }}">
                                    {{ $post->trashed() ? $statusLabels['trash'] : $statusLabels[$post->status] }}
                                </span>
                            </td>
                            <td>{{ $post->updated_at->diffForHumans() }}</td>
                            <td>
                                <div class="table-actions">
                                    @if ($post->trashed())
                                        @if (auth()->user()->hasPermission('news.update'))
                                            <form method="post" action="{{ route('admin.posts.restore-deleted', $post) }}">
                                                @csrf
                                                <button
                                                    class="table-action table-action--restore"
                                                    type="submit"
                                                    title="Restaurar"
                                                    aria-label="Restaurar {{ $post->title }}"
                                                >
                                                    <img src="{{ asset('images/admin/icons/guardar.png') }}" alt="">
                                                    <span>Restaurar</span>
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <a
                                            class="table-action table-action--preview"
                                            href="{{ route('admin.posts.preview', $post) }}"
                                            target="_blank"
                                            rel="noopener"
                                            title="Vista previa"
                                            aria-label="Vista previa de {{ $post->title }}"
                                        >
                                            <img src="{{ asset('images/admin/icons/vista.png') }}" alt="">
                                            <span>Vista previa</span>
                                        </a>
                                        @if (auth()->user()->hasPermission('news.update'))
                                            <a
                                                class="table-action table-action--edit"
                                                href="{{ route('admin.posts.edit', $post) }}"
                                                title="Editar"
                                                aria-label="Editar {{ $post->title }}"
                                            >
                                                <img src="{{ asset('images/admin/icons/editar.png') }}" alt="">
                                                <span>Editar</span>
                                            </a>
                                            <form
                                                method="post"
                                                action="{{ route('admin.posts.destroy', $post) }}"
                                                data-confirm-delete="¿Enviar esta noticia a la papelera?"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="table-action table-action--delete"
                                                    type="submit"
                                                    title="Eliminar"
                                                    aria-label="Enviar {{ $post->title }} a la papelera"
                                                >
                                                    <img src="{{ asset('images/admin/icons/eliminar2.png') }}" alt="">
                                                    <span>Eliminar</span>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No se encontraron noticias.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-pagination">
            <p>
                Mostrando {{ $posts->firstItem() ?? 0 }}–{{ $posts->lastItem() ?? 0 }}
                de {{ $posts->total() }} noticias
            </p>
            {{ $posts->onEachSide(1)->links() }}
        </div>
    </section>
@endsection
