@extends('layouts.admin')

@section('title', 'Categorías')
@section('eyebrow', 'Organización editorial')
@section('heading', 'Categorías')

@section('content')
    @php
        $categoryValidationFields = [
            'name', 'slug', 'parent_id', 'color', 'icon', 'description',
            'relevance_weight', 'homepage_limit', 'homepage_layout',
            'is_active', 'show_in_menu', 'show_on_home', 'seo_title', 'seo_description',
        ];
        $openCategoryContext = $errors->hasAny($categoryValidationFields)
            ? old('form_context', 'create')
            : null;
    @endphp

    <div
        class="category-admin"
        data-category-admin
        @if ($openCategoryContext) data-category-open-context="{{ $openCategoryContext }}" @endif
        data-category-old-values="{{ json_encode(old(), JSON_UNESCAPED_UNICODE) }}"
    >
        <div class="taxonomy-metrics category-metrics" aria-label="Resumen de categorías">
            <article><span>Total</span><strong>{{ $stats['total'] }}</strong></article>
            <article><span>Activas</span><strong>{{ $stats['active'] }}</strong></article>
            <article><span>Inactivas</span><strong>{{ $stats['inactive'] }}</strong></article>
            <article><span>Papelera</span><strong>{{ $stats['trash'] }}</strong></article>
        </div>

        <section class="panel category-create-launch">
            <div>
                <span class="eyebrow">Nueva categoría</span>
                <h2>Amplía la organización editorial</h2>
                <p>Crea categorías principales o subcategorías sin abandonar este listado.</p>
            </div>
            <button class="button button--primary" type="button" data-category-create-open>
                <span aria-hidden="true">＋</span>
                Nueva categoría
            </button>
        </section>

        <section class="panel taxonomy-toolbar category-toolbar">
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

        <div class="category-list-heading">
            <div>
                <span class="eyebrow">{{ $status === 'trash' ? 'Papelera editorial' : 'Árbol editorial' }}</span>
                <h2>{{ $status === 'trash' ? 'Categorías eliminadas' : 'Categorías y subcategorías' }}</h2>
                <p>
                    {{ $status === 'trash'
                        ? 'Restaura una categoría o elimínala definitivamente.'
                        : 'La sangría identifica la relación entre categorías principales y categorías hijas.' }}
                </p>
            </div>
            @if ($status !== 'trash')
                <button class="button button--primary" type="submit" form="category-order-form">
                    Guardar orden
                </button>
                <form id="category-order-form" method="post" action="{{ route('admin.categories.reorder') }}">@csrf</form>
            @endif
        </div>

        <section class="panel table-panel category-table-panel">
            <div class="responsive-table category-table-wrap">
                <table class="taxonomy-table category-table {{ $status === 'trash' ? 'category-table--trash' : '' }}">
                    <thead>
                        <tr>
                            @if ($status !== 'trash') <th>Orden</th> @endif
                            <th>Categoría</th>
                            <th>Jerarquía</th>
                            <th>Visibilidad</th>
                            <th>Noticias</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody @if ($status !== 'trash') data-sortable-categories @endif>
                        @forelse ($categories as $category)
                            @php
                                $editPayload = [
                                    'id' => $category->id,
                                    'name' => $category->name,
                                    'slug' => $category->slug,
                                    'parent_id' => $category->parent_id,
                                    'color' => $category->color,
                                    'icon' => $category->icon,
                                    'description' => $category->description,
                                    'relevance_weight' => $category->relevance_weight,
                                    'homepage_limit' => $category->homepage_limit,
                                    'homepage_layout' => $category->homepage_layout,
                                    'is_active' => (bool) $category->is_active,
                                    'show_in_menu' => (bool) $category->show_in_menu,
                                    'show_on_home' => (bool) $category->show_on_home,
                                    'seo_title' => $category->seo_title,
                                    'seo_description' => $category->seo_description,
                                    'update_url' => route('admin.categories.update', $category->id),
                                ];
                            @endphp
                            <tr @if ($status !== 'trash') draggable="true" data-category-row @endif>
                                @if ($status !== 'trash')
                                    <td data-label="Orden">
                                        <div class="category-order-control">
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
                                        </div>
                                    </td>
                                @endif
                                <td data-label="Categoría">
                                    <div class="taxonomy-name" style="--tree-depth: {{ $category->tree_depth ?? 0 }}">
                                        <span class="taxonomy-name__icon" aria-hidden="true">{{ $category->icon ?: '◫' }}</span>
                                        <span>
                                            <strong>
                                                <i class="category-color" style="--category-admin-color: {{ $category->color }}"></i>
                                                {{ $category->name }}
                                            </strong>
                                            <small>/{{ $category->slug }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Jerarquía">
                                    <strong>{{ $category->parent?->name ?? 'Categoría principal' }}</strong>
                                    <small>{{ $category->children_count }} subcategorías</small>
                                </td>
                                <td data-label="Visibilidad">
                                    @if ($status === 'trash')
                                        <span class="badge badge--trash">Papelera</span>
                                        <small>Eliminada {{ $category->deleted_at?->diffForHumans() }}</small>
                                    @else
                                        <span class="badge {{ $category->is_active ? 'badge--success' : 'badge--muted' }}">
                                            {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                        <small>
                                            {{ $category->show_in_menu ? 'Menú' : 'Sin menú' }}
                                            ·
                                            {{ $category->show_on_home ? 'Portada' : 'Sin portada' }}
                                        </small>
                                    @endif
                                </td>
                                <td data-label="Noticias">
                                    <strong>{{ $category->posts_count }}</strong>
                                    <small>publicaciones directas</small>
                                </td>
                                <td data-label="Acciones">
                                    @if ($status === 'trash')
                                        <div class="category-actions">
                                            <form method="post" action="{{ route('admin.categories.restore', $category->id) }}">
                                                @csrf
                                                <button class="category-action category-action--restore" type="submit">
                                                    <span aria-hidden="true">↻</span> Restaurar
                                                </button>
                                            </form>
                                            <form
                                                method="post"
                                                action="{{ route('admin.categories.force-destroy', $category->id) }}"
                                                data-confirm-delete="Esta categoría será eliminada definitivamente."
                                                data-confirm-title="Eliminar categoría definitivamente"
                                                data-confirm-name="{{ $category->name }}"
                                                data-confirm-button="Eliminar definitivamente"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="category-action category-action--delete" type="submit">
                                                    <span aria-hidden="true">⌫</span> Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="category-actions">
                                            <button
                                                class="category-action category-action--edit"
                                                type="button"
                                                data-category-edit
                                                data-category-id="{{ $category->id }}"
                                                data-category-payload="{{ json_encode($editPayload, JSON_UNESCAPED_UNICODE) }}"
                                            >
                                                <span aria-hidden="true">✎</span> Editar
                                            </button>
                                            <form
                                                method="post"
                                                action="{{ route('admin.categories.destroy', $category->id) }}"
                                                data-confirm-delete="La categoría se enviará a la papelera sin perder sus noticias."
                                                data-confirm-title="Eliminar categoría"
                                                data-confirm-name="{{ $category->name }}"
                                                data-confirm-button="Enviar a la papelera"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                @if ($category->posts_count > 0)
                                                    <div class="category-delete-replacement" data-delete-modal-field hidden>
                                                        <label>
                                                            Reasignar {{ $category->posts_count }} noticias a
                                                            <select name="replacement_category_id" data-delete-modal-required>
                                                                <option value="">Selecciona una categoría de destino</option>
                                                                @foreach ($parentOptions as $replacement)
                                                                    @if ($replacement->id !== $category->id)
                                                                        <option value="{{ $replacement->id }}">{{ $replacement->name }}</option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                        </label>
                                                        <small>Las noticias conservarán su contenido y pasarán a la categoría seleccionada.</small>
                                                    </div>
                                                @endif
                                                <button class="category-action category-action--delete" type="submit">
                                                    <span aria-hidden="true">⌫</span> Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="category-table__empty">
                                <td colspan="{{ $status === 'trash' ? 5 : 6 }}">No se encontraron categorías.</td>
                            </tr>
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

        <dialog
            class="category-editor-dialog"
            data-category-create-dialog
            aria-labelledby="category-create-title"
        >
            <form method="post" action="{{ route('admin.categories.store') }}" class="category-editor-form">
                @csrf
                <header class="category-editor-dialog__header">
                    <div class="category-editor-dialog__identity">
                        <span class="category-editor-dialog__icon" aria-hidden="true">＋</span>
                        <div>
                            <span class="eyebrow">Organización editorial</span>
                            <h2 id="category-create-title">Nueva categoría</h2>
                        </div>
                    </div>
                    <button type="button" data-category-dialog-close aria-label="Cerrar">×</button>
                </header>
                <div class="category-editor-dialog__body">
                    @include('admin.taxonomy.partials.category-form-fields', ['mode' => 'create'])
                </div>
                <footer class="category-editor-dialog__footer">
                    <small>Las categorías hijas representan temas editoriales, no ubicaciones geográficas.</small>
                    <div>
                        <button class="button button--quiet" type="button" data-category-dialog-close>Cancelar</button>
                        <button class="button button--primary" type="submit">Crear categoría</button>
                    </div>
                </footer>
            </form>
        </dialog>

        <dialog
            class="category-editor-dialog"
            data-category-edit-dialog
            aria-labelledby="category-edit-title"
        >
            <form method="post" action="{{ route('admin.categories.index') }}" class="category-editor-form" data-category-edit-form>
                @csrf
                @method('PUT')
                <header class="category-editor-dialog__header">
                    <div class="category-editor-dialog__identity">
                        <span class="category-editor-dialog__icon category-editor-dialog__icon--edit" aria-hidden="true">✎</span>
                        <div>
                            <span class="eyebrow">Configuración editorial</span>
                            <h2 id="category-edit-title">Editar categoría</h2>
                        </div>
                    </div>
                    <button type="button" data-category-dialog-close aria-label="Cerrar">×</button>
                </header>
                <div class="category-editor-dialog__body">
                    @include('admin.taxonomy.partials.category-form-fields', ['mode' => 'edit'])
                </div>
                <footer class="category-editor-dialog__footer">
                    <small data-category-edit-summary>Actualiza la organización y visibilidad de la categoría.</small>
                    <div>
                        <button class="button button--quiet" type="button" data-category-dialog-close>Cancelar</button>
                        <button class="button button--primary category-save-button" type="submit">Guardar cambios</button>
                    </div>
                </footer>
            </form>
        </dialog>
    </div>
@endsection
