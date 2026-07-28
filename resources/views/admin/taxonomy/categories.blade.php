@extends('layouts.admin')

@section('title', 'Categorías')
@section('eyebrow', 'Organización editorial')
@section('heading', 'Categorías')

@section('content')
    <section class="panel taxonomy-create">
        <div class="panel__header">
            <div><span class="eyebrow">Nueva categoría</span><h2>Crear categoría</h2></div>
        </div>
        <form method="post" action="{{ route('admin.categories.store') }}" class="form-stack">
            @csrf
            <div class="form-grid form-grid--three">
                <label>Nombre <input type="text" name="name" maxlength="100" required></label>
                <label>Slug <input type="text" name="slug" maxlength="120" placeholder="automático-si-se-deja-vacío"></label>
                <label>Color <input type="color" name="color" value="#d9251b" required></label>
            </div>
            <label>Descripción <textarea name="description" rows="2" maxlength="1000"></textarea></label>
            <div class="form-grid form-grid--three">
                <label>Relevancia <input type="number" name="relevance_weight" value="50" min="0" max="1000" required></label>
                <label>Límite en portada <input type="number" name="homepage_limit" value="4" min="1" max="12" required></label>
                <label>Diseño
                    <select name="homepage_layout">
                        <option value="standard">Estándar</option>
                        <option value="featured">Destacada</option>
                        <option value="grid">Cuadrícula</option>
                    </select>
                </label>
            </div>
            <button class="button button--primary" type="submit">Crear categoría</button>
        </form>
    </section>

    <div class="page-actions">
        <p>Arrastra las filas o cambia su número para definir el orden del menú.</p>
        <button class="button button--primary" type="submit" form="category-order-form">Guardar orden</button>
    </div>

    <form id="category-order-form" method="post" action="{{ route('admin.categories.reorder') }}">
        @csrf
    </form>

    <section class="panel table-panel">
        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Categoría</th>
                        <th>Relevancia</th>
                        <th>Visibilidad</th>
                        <th>Noticias</th>
                        <th><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody data-sortable-categories>
                    @foreach ($categories as $category)
                        <tr draggable="true" data-category-row>
                            <td>
                                <span class="drag-handle" aria-hidden="true">⋮⋮</span>
                                <input
                                    class="order-input"
                                    type="number"
                                    name="order[{{ $category->id }}]"
                                    value="{{ $category->display_order }}"
                                    min="1"
                                    max="10000"
                                    form="category-order-form"
                                    aria-label="Orden de {{ $category->name }}"
                                >
                            </td>
                            <td>
                                <strong><i class="category-color" style="--category-admin-color: {{ $category->color }}"></i>{{ $category->name }}</strong>
                                <small>{{ $category->slug }}</small>
                            </td>
                            <td>{{ $category->relevance_weight }}</td>
                            <td>
                                <span class="badge {{ $category->is_active ? 'badge--success' : 'badge--muted' }}">
                                    {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                                <small>{{ $category->show_in_menu ? 'Menú' : 'Sin menú' }} · {{ $category->show_on_home ? 'Portada' : 'Sin portada' }}</small>
                            </td>
                            <td>{{ $category->posts_count }}</td>
                            <td>
                                <details class="row-editor">
                                    <summary>Configurar</summary>
                                    <div class="row-editor__panel">
                                        <form method="post" action="{{ route('admin.categories.update', $category->id) }}" class="form-stack form-stack--compact">
                                            @csrf
                                            @method('PUT')
                                            <label>Nombre <input type="text" name="name" value="{{ $category->name }}" required></label>
                                            <label>Slug <input type="text" name="slug" value="{{ $category->slug }}" required></label>
                                            <label>Color <input type="color" name="color" value="{{ $category->color }}" required></label>
                                            <label>Descripción <textarea name="description" rows="2">{{ $category->description }}</textarea></label>
                                            <div class="form-grid">
                                                <label>Relevancia <input type="number" name="relevance_weight" value="{{ $category->relevance_weight }}" min="0" max="1000" required></label>
                                                <label>Límite portada <input type="number" name="homepage_limit" value="{{ $category->homepage_limit }}" min="1" max="12" required></label>
                                            </div>
                                            <label>Diseño
                                                <select name="homepage_layout">
                                                    @foreach (['standard' => 'Estándar', 'featured' => 'Destacada', 'grid' => 'Cuadrícula'] as $value => $label)
                                                        <option value="{{ $value }}" @selected($category->homepage_layout === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked($category->is_active)><span>Activa</span></label>
                                            <label class="check-row"><input type="checkbox" name="show_in_menu" value="1" @checked($category->show_in_menu)><span>Mostrar en menú</span></label>
                                            <label class="check-row"><input type="checkbox" name="show_on_home" value="1" @checked($category->show_on_home)><span>Usar en portada</span></label>
                                            <button class="button button--primary" type="submit">Guardar</button>
                                        </form>
                                        <form method="post" action="{{ route('admin.categories.destroy', $category->id) }}" onsubmit="return confirm('¿Eliminar esta categoría?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="danger-link" type="submit" @disabled($category->posts_count > 0)>
                                                {{ $category->posts_count > 0 ? 'Contiene noticias' : 'Eliminar categoría' }}
                                            </button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
