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
                            <td><span class="badge badge--{{ $post->status }}">{{ $statusLabels[$post->status] }}</span></td>
                            <td>{{ $post->updated_at->diffForHumans() }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-link" href="{{ route('admin.posts.preview', $post) }}" target="_blank">Vista previa</a>
                                    @if (auth()->user()->hasPermission('news.update'))
                                        <a class="table-link" href="{{ route('admin.posts.edit', $post) }}">Editar</a>
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
        {{ $posts->links() }}
    </section>
@endsection
