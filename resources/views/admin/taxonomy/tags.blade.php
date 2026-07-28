@extends('layouts.admin')

@section('title', 'Etiquetas')
@section('eyebrow', 'Organización editorial')
@section('heading', 'Etiquetas')

@section('content')
    <section class="panel taxonomy-create">
        <div class="panel__header">
            <div><span class="eyebrow">Nueva etiqueta</span><h2>Crear etiqueta</h2></div>
        </div>
        <form method="post" action="{{ route('admin.tags.store') }}" class="form-stack">
            @csrf
            <div class="form-grid">
                <label>Nombre <input type="text" name="name" maxlength="100" required></label>
                <label>Slug <input type="text" name="slug" maxlength="120" placeholder="automático-si-se-deja-vacío"></label>
            </div>
            <button class="button button--primary" type="submit">Crear etiqueta</button>
        </form>
    </section>

    <section class="tag-admin-grid">
        @foreach ($tags as $tag)
            <article class="panel tag-admin-card">
                <div>
                    <span class="tag-chip">#{{ $tag->name }}</span>
                    <small>{{ $tag->posts_count }} noticia(s) · {{ $tag->slug }}</small>
                </div>
                <details>
                    <summary>Editar</summary>
                    <form method="post" action="{{ route('admin.tags.update', $tag->id) }}" class="form-stack form-stack--compact">
                        @csrf
                        @method('PUT')
                        <label>Nombre <input type="text" name="name" value="{{ $tag->name }}" required></label>
                        <label>Slug <input type="text" name="slug" value="{{ $tag->slug }}" required></label>
                        <button class="button button--quiet" type="submit">Guardar</button>
                    </form>
                </details>
                @if ($tags->count() > 1)
                    <details>
                        <summary>Combinar con otra</summary>
                        <form method="post" action="{{ route('admin.tags.merge', $tag->id) }}" class="form-stack form-stack--compact">
                            @csrf
                            <label>Etiqueta de destino
                                <select name="target_id" required>
                                    @foreach ($tags->where('id', '!=', $tag->id) as $target)
                                        <option value="{{ $target->id }}">{{ $target->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button class="button button--quiet" type="submit">Combinar</button>
                        </form>
                    </details>
                @endif
                <form method="post" action="{{ route('admin.tags.destroy', $tag->id) }}" onsubmit="return confirm('¿Eliminar esta etiqueta?')">
                    @csrf
                    @method('DELETE')
                    <button class="danger-link" type="submit" @disabled($tag->posts_count > 0)>
                        {{ $tag->posts_count > 0 ? 'En uso' : 'Eliminar' }}
                    </button>
                </form>
            </article>
        @endforeach
    </section>
@endsection
