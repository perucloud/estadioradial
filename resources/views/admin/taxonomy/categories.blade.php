@extends('layouts.admin')

@section('title', 'Categorías')
@section('eyebrow', 'Organización editorial')
@section('heading', 'Categorías')

@section('content')
    <div class="taxonomy-metrics" aria-label="Resumen de categorías">
        <article><span>Total</span><strong>{{ $stats['total'] }}</strong></article>
        <article><span>Activas</span><strong>{{ $stats['active'] }}</strong></article>
        <article><span>Inactivas</span><strong>{{ $stats['inactive'] }}</strong></article>
        <article><span>Papelera</span><strong>{{ $stats['trash'] }}</strong></article>
    </div>

    <details class="panel taxonomy-create" @if ($errors->hasAny(['name', 'slug', 'parent_id', 'color'])) open @endif>
        <summary class="taxonomy-create__summary">
            <span>
                <span class="eyebrow">Nueva categoría</span>
                <strong>Crear categoría editorial</strong>
            </span>
            <span class="button button--primary">+ Nueva categoría</span>
        </summary>
        <form method="post" action="{{ route('admin.categories.store') }}" class="form-stack taxonomy-form">
            @csrf
            <div class="form-grid form-grid--three">
                <label>
                    Nombre
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="100" required>
                    @error('name') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Slug
                    <input type="text" name="slug" value="{{ old('slug') }}" maxlength="120" placeholder="Se genera automáticamente">
                    @error('slug') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Categoría superior
                    <select name="parent_id">
                        <option value="">Categoría principal</option>
                        @foreach ($parentOptions as $option)
                            <option value="{{ $option->id }}" @selected(old('parent_id') == $option->id)>
                                {{ str_repeat('— ', $option->tree_depth) }}{{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id') <small class="field-error">{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="form-grid form-grid--three">
                <label>Color <input type="color" name="color" value="{{ old('color', '#d9251b') }}" required></label>
                <label>Icono <input type="text" name="icon" value="{{ old('icon') }}" maxlength="100" placeholder="Opcional: 🏛️"></label>
                <label>
                    Diseño
                    <select name="homepage_layout">
                        @foreach (['standard' => 'Estándar', 'featured' => 'Destacada', 'grid' => 'Cuadrícula'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('homepage_layout', 'standard') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label>Descripción <textarea name="description" rows="3" maxlength="1000">{{ old('description') }}</textarea></label>

            <div class="form-grid form-grid--three">
                <label>Relevancia <input type="number" name="relevance_weight" value="{{ old('relevance_weight', 50) }}" min="0" max="1000" required></label>
                <label>Límite en portada <input type="number" name="homepage_limit" value="{{ old('homepage_limit', 4) }}" min="1" max="12" required></label>
                <div class="taxonomy-visibility">
                    <input type="hidden" name="is_active" value="0">
                    <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))><span>Activa</span></label>
                    <input type="hidden" name="show_in_menu" value="0">
                    <label class="check-row"><input type="checkbox" name="show_in_menu" value="1" @checked(old('show_in_menu', true))><span>Mostrar en menú</span></label>
                    <input type="hidden" name="show_on_home" value="0">
                    <label class="check-row"><input type="checkbox" name="show_on_home" value="1" @checked(old('show_on_home', true))><span>Mostrar en portada</span></label>
                </div>
            </div>

            <div class="form-grid">
                <label>Título SEO <input type="text" name="seo_title" value="{{ old('seo_title') }}" maxlength="70" placeholder="Se completa desde el nombre"></label>
                <label>Descripción SEO <textarea name="seo_description" rows="2" maxlength="170" placeholder="Se completa desde la descripción">{{ old('seo_description') }}</textarea></label>
            </div>

            <div class="form-actions">
                <small class="field-help">Las categorías hijas sirven para temas editoriales, no para ubicaciones geográficas.</small>
                <button class="button button--primary" type="submit">Crear categoría</button>
            </div>
        </form>
    </details>

    <section class="panel taxonomy-toolbar">
        <form method="get" action="{{ route('admin.categories.index') }}" class="taxonomy-filters" data-auto-filter>
            <label>
                <span class="sr-only">Buscar categorías</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Buscar por nombre, slug o descripción">
            </label>
            <label>
                <span class="sr-only">Estado</span>
                <select name="status">
                    <option value="">Todos los estados</option>
                    <option value="active" @selected($status === 'active')>Activas</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactivas</option>
                    <option value="trash" @selected($status === 'trash')>Papelera</option>
                </select>
            </label>
            <label>
                <span class="sr-only">Categoría superior</span>
                <select name="parent">
                    <option value="">Cualquier nivel</option>
                    <option value="root" @selected($parentFilter === 'root')>Solo principales</option>
                    @foreach ($parentOptions as $option)
                        <option value="{{ $option->id }}" @selected($parentFilter === (string) $option->id)>
                            Hijas de {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">Resultados por página</span>
                <select name="per_page">
                    @foreach ([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} por página</option>
                    @endforeach
                </select>
            </label>
            <button class="button button--primary" type="submit">Filtrar</button>
            @if ($search !== '' || $status !== '' || $parentFilter !== '')
                <a class="button button--quiet" href="{{ route('admin.categories.index') }}">Limpiar</a>
            @endif
        </form>
    </section>

    @if ($errors->has('category'))
        <div class="alert alert--error">{{ $errors->first('category') }}</div>
    @endif

    @if ($status !== 'trash')
        <div class="page-actions">
            <p>El árbol muestra la relación entre categorías principales y subcategorías.</p>
            <button class="button button--primary" type="submit" form="category-order-form">Guardar orden</button>
        </div>
        <form id="category-order-form" method="post" action="{{ route('admin.categories.reorder') }}">@csrf</form>
    @endif

    <section class="panel table-panel">
        <div class="responsive-table">
            <table class="taxonomy-table">
                <thead>
                    <tr>
                        @if ($status !== 'trash') <th>Orden</th> @endif
                        <th>Categoría</th>
                        <th>Jerarquía</th>
                        <th>Visibilidad</th>
                        <th>Noticias</th>
                        <th><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody @if ($status !== 'trash') data-sortable-categories @endif>
                    @forelse ($categories as $category)
                        <tr @if ($status !== 'trash') draggable="true" data-category-row @endif>
                            @if ($status !== 'trash')
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
                            @endif
                            <td>
                                <div class="taxonomy-name" style="--tree-depth: {{ $category->tree_depth ?? 0 }}">
                                    @if ($category->icon)<span class="taxonomy-name__icon" aria-hidden="true">{{ $category->icon }}</span>@endif
                                    <span>
                                        <strong><i class="category-color" style="--category-admin-color: {{ $category->color }}"></i>{{ $category->name }}</strong>
                                        <small>{{ $category->slug }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <strong>{{ $category->parent?->name ?? 'Principal' }}</strong>
                                <small>{{ $category->children_count }} subcategorías</small>
                            </td>
                            <td>
                                @if ($status === 'trash')
                                    <span class="badge badge--trash">Papelera</span>
                                    <small>Eliminada {{ $category->deleted_at?->diffForHumans() }}</small>
                                @else
                                    <span class="badge {{ $category->is_active ? 'badge--success' : 'badge--muted' }}">
                                        {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                    <small>{{ $category->show_in_menu ? 'Menú' : 'Sin menú' }} · {{ $category->show_on_home ? 'Portada' : 'Sin portada' }}</small>
                                @endif
                            </td>
                            <td><strong>{{ $category->posts_count }}</strong><small>publicaciones directas</small></td>
                            <td>
                                @if ($status === 'trash')
                                    <div class="taxonomy-row-actions">
                                        <form method="post" action="{{ route('admin.categories.restore', $category->id) }}">
                                            @csrf
                                            <button class="button button--quiet button--compact" type="submit">Restaurar</button>
                                        </form>
                                        <form method="post" action="{{ route('admin.categories.force-destroy', $category->id) }}" onsubmit="return confirm('¿Eliminar definitivamente esta categoría?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="danger-link" type="submit">Eliminar definitivamente</button>
                                        </form>
                                    </div>
                                @else
                                    <details class="row-editor">
                                        <summary>Editar</summary>
                                        <div class="row-editor__panel row-editor__panel--category">
                                            <form method="post" action="{{ route('admin.categories.update', $category->id) }}" class="form-stack form-stack--compact">
                                                @csrf
                                                @method('PUT')
                                                <div class="form-grid">
                                                    <label>Nombre <input type="text" name="name" value="{{ $category->name }}" maxlength="100" required></label>
                                                    <label>Slug <input type="text" name="slug" value="{{ $category->slug }}" maxlength="120" required></label>
                                                </div>
                                                <label>
                                                    Categoría superior
                                                    <select name="parent_id">
                                                        <option value="">Categoría principal</option>
                                                        @foreach ($parentOptions as $option)
                                                            @if ($option->id !== $category->id)
                                                                <option value="{{ $option->id }}" @selected($category->parent_id === $option->id)>
                                                                    {{ str_repeat('— ', $option->tree_depth) }}{{ $option->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <div class="form-grid form-grid--three">
                                                    <label>Color <input type="color" name="color" value="{{ $category->color }}" required></label>
                                                    <label>Icono <input type="text" name="icon" value="{{ $category->icon }}" maxlength="100"></label>
                                                    <label>Relevancia <input type="number" name="relevance_weight" value="{{ $category->relevance_weight }}" min="0" max="1000" required></label>
                                                </div>
                                                <label>Descripción <textarea name="description" rows="2" maxlength="1000">{{ $category->description }}</textarea></label>
                                                <div class="form-grid">
                                                    <label>Límite portada <input type="number" name="homepage_limit" value="{{ $category->homepage_limit }}" min="1" max="12" required></label>
                                                    <label>
                                                        Diseño
                                                        <select name="homepage_layout">
                                                            @foreach (['standard' => 'Estándar', 'featured' => 'Destacada', 'grid' => 'Cuadrícula'] as $value => $label)
                                                                <option value="{{ $value }}" @selected($category->homepage_layout === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                </div>
                                                <input type="hidden" name="is_active" value="0">
                                                <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked($category->is_active)><span>Activa</span></label>
                                                <input type="hidden" name="show_in_menu" value="0">
                                                <label class="check-row"><input type="checkbox" name="show_in_menu" value="1" @checked($category->show_in_menu)><span>Mostrar en menú</span></label>
                                                <input type="hidden" name="show_on_home" value="0">
                                                <label class="check-row"><input type="checkbox" name="show_on_home" value="1" @checked($category->show_on_home)><span>Usar en portada</span></label>
                                                <label>Título SEO <input type="text" name="seo_title" value="{{ $category->seo_title }}" maxlength="70"></label>
                                                <label>Descripción SEO <textarea name="seo_description" rows="2" maxlength="170">{{ $category->seo_description }}</textarea></label>
                                                <button class="button button--primary" type="submit">Guardar cambios</button>
                                            </form>

                                            <form method="post" action="{{ route('admin.categories.destroy', $category->id) }}" class="form-stack form-stack--compact" onsubmit="return confirm('¿Enviar esta categoría a la papelera?')">
                                                @csrf
                                                @method('DELETE')
                                                @if ($category->posts_count > 0)
                                                    <label>
                                                        Reasignar {{ $category->posts_count }} noticias a
                                                        <select name="replacement_category_id" required>
                                                            <option value="">Seleccionar reemplazo</option>
                                                            @foreach ($parentOptions as $replacement)
                                                                @if ($replacement->id !== $category->id)
                                                                    <option value="{{ $replacement->id }}">{{ $replacement->name }}</option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                @endif
                                                <button class="danger-link" type="submit">Enviar a la papelera</button>
                                            </form>
                                        </div>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $status === 'trash' ? 5 : 6 }}">No se encontraron categorías.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-pagination">
            <p>
                Mostrando {{ $categories->firstItem() ?? 0 }}–{{ $categories->lastItem() ?? 0 }}
                de {{ $categories->total() }} categorías
            </p>
            {{ $categories->onEachSide(1)->links() }}
        </div>
    </section>
@endsection
