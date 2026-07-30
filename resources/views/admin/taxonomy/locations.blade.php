@extends('layouts.admin')

@section('title', 'Ubicaciones')
@section('eyebrow', 'Cobertura geográfica')
@section('heading', 'Ubicaciones')

@section('content')
    @php
        $locationValidationFields = [
            'name', 'slug', 'type', 'parent_id', 'country_code', 'ubigeo',
            'latitude', 'longitude', 'description', 'seo_title', 'seo_description', 'is_active',
        ];
        $openLocationContext = $errors->hasAny($locationValidationFields)
            ? old('form_context', 'create')
            : null;
        $typeIcons = [
            'country' => '🌐',
            'region' => '🗺️',
            'province' => '📍',
            'district' => '🏘️',
        ];
    @endphp

    <div
        class="category-admin location-admin"
        data-location-admin
        @if ($openLocationContext) data-location-open-context="{{ $openLocationContext }}" @endif
        data-location-old-values="{{ json_encode(old(), JSON_UNESCAPED_UNICODE) }}"
    >
        <div class="taxonomy-metrics taxonomy-metrics--locations location-metrics" aria-label="Resumen territorial">
            <article><span>Total</span><strong>{{ $stats['total'] }}</strong></article>
            <article><span>Países</span><strong>{{ $stats['countries'] }}</strong></article>
            <article><span>Regiones</span><strong>{{ $stats['regions'] }}</strong></article>
            <article><span>Provincias</span><strong>{{ $stats['provinces'] }}</strong></article>
            <article><span>Distritos</span><strong>{{ $stats['districts'] }}</strong></article>
            <article><span>Papelera</span><strong>{{ $stats['trash'] }}</strong></article>
        </div>

        <section class="panel category-create-launch location-create-launch">
            <div>
                <span class="eyebrow">Nueva ubicación</span>
                <h2>Amplía el catálogo territorial</h2>
                <p>Agrega una ubicación personalizada únicamente cuando no exista en el catálogo importado.</p>
            </div>
            <button class="button button--primary" type="button" data-location-create-open>
                <span aria-hidden="true">＋</span>
                Nueva ubicación
            </button>
        </section>

        <section class="panel taxonomy-toolbar category-toolbar">
            <form method="get" action="{{ route('admin.locations.index') }}" class="taxonomy-filters taxonomy-filters--locations" data-auto-filter>
                <label><span class="sr-only">Buscar</span><input type="search" name="q" value="{{ $search }}" placeholder="Buscar nombre, slug o UBIGEO"></label>
                <label>
                    <span class="sr-only">Tipo</span>
                    <select name="type">
                        <option value="">Todos los niveles</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected($typeFilter === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
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
                    <span class="sr-only">Ubicación superior</span>
                    <select name="parent">
                        <option value="">Cualquier ubicación superior</option>
                        <option value="root" @selected($parentFilter === 'root')>Solo países</option>
                        @foreach ($filterParentOptions as $option)
                            <option value="{{ $option->id }}" @selected($parentFilter === (string) $option->id)>Dentro de {{ $option->name }}</option>
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
                @if ($search !== '' || $status !== '' || $typeFilter !== '' || $parentFilter !== '')
                    <a class="button button--quiet" href="{{ route('admin.locations.index') }}">Limpiar</a>
                @endif
            </form>
        </section>

        @if ($errors->has('location'))
            <div class="alert alert--error">{{ $errors->first('location') }}</div>
        @endif

        <div class="category-list-heading">
            <div>
                <span class="eyebrow">{{ $status === 'trash' ? 'Papelera territorial' : 'Jerarquía geográfica' }}</span>
                <h2>{{ $status === 'trash' ? 'Ubicaciones eliminadas' : 'Países, regiones, provincias y distritos' }}</h2>
                <p>
                    {{ $status === 'trash'
                        ? 'Restaura una ubicación o elimínala definitivamente.'
                        : 'La sangría representa el nivel geográfico y la dependencia territorial.' }}
                </p>
            </div>
            @if ($status !== 'trash')
                <button class="button button--primary" type="submit" form="location-order-form">Guardar orden</button>
                <form id="location-order-form" method="post" action="{{ route('admin.locations.reorder') }}">@csrf</form>
            @endif
        </div>

        <section class="panel table-panel category-table-panel location-table-panel">
            <div class="responsive-table category-table-wrap">
                <table class="taxonomy-table category-table location-admin-table {{ $status === 'trash' ? 'location-admin-table--trash' : '' }}">
                    <thead>
                        <tr>
                            @if ($status !== 'trash') <th>Orden</th> @endif
                            <th>Ubicación</th>
                            <th>Tipo</th>
                            <th>Códigos</th>
                            <th>Noticias</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody @if ($status !== 'trash') data-sortable-locations @endif>
                        @forelse ($locations as $location)
                            @php
                                $editPayload = [
                                    'id' => $location->id,
                                    'name' => $location->name,
                                    'slug' => $location->slug,
                                    'type' => $location->type,
                                    'parent_id' => $location->parent_id,
                                    'country_code' => $location->country_code,
                                    'ubigeo' => $location->ubigeo,
                                    'latitude' => $location->latitude,
                                    'longitude' => $location->longitude,
                                    'description' => $location->description,
                                    'seo_title' => $location->seo_title,
                                    'seo_description' => $location->seo_description,
                                    'is_active' => (bool) $location->is_active,
                                    'update_url' => route('admin.locations.update', $location),
                                ];
                                $deleteProtected = $location->children_count > 0 || $location->posts_count > 0;
                                $deleteProtection = $location->children_count > 0
                                    ? 'Contiene divisiones territoriales'
                                    : ($location->posts_count > 0 ? 'Está siendo usada por noticias' : null);
                            @endphp
                            <tr @if ($status !== 'trash') draggable="true" data-location-row @endif>
                                @if ($status !== 'trash')
                                    <td data-label="Orden">
                                        <div class="category-order-control">
                                            <span class="drag-handle" aria-hidden="true">⋮⋮</span>
                                            <input
                                                class="order-input"
                                                type="number"
                                                name="order[{{ $location->id }}]"
                                                value="{{ $location->display_order }}"
                                                min="1"
                                                max="10000"
                                                form="location-order-form"
                                                aria-label="Orden de {{ $location->name }}"
                                            >
                                        </div>
                                    </td>
                                @endif
                                <td data-label="Ubicación">
                                    <div class="taxonomy-name" style="--tree-depth: {{ $location->tree_depth ?? 0 }}">
                                        <span class="location-type-icon" aria-hidden="true">{{ $typeIcons[$location->type] }}</span>
                                        <span>
                                            <strong>{{ $location->name }}</strong>
                                            <small>/{{ $location->slug }} · superior: {{ $location->parent?->name ?? 'ninguna' }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Tipo">
                                    <span class="location-type-badge location-type-badge--{{ $location->type }}">{{ $location->typeLabel() }}</span>
                                    <small>{{ $location->children_count }} elementos hijos</small>
                                </td>
                                <td data-label="Códigos">
                                    <strong>{{ $location->country_code ?? '—' }}</strong>
                                    <small>UBIGEO: {{ $location->ubigeo ?? 'sin registrar' }}</small>
                                    <small>{{ $location->sourceLabel() }}</small>
                                </td>
                                <td data-label="Noticias">
                                    <strong>{{ $location->posts_count }}</strong>
                                    <small>publicaciones</small>
                                </td>
                                <td data-label="Estado">
                                    @if ($status === 'trash')
                                        <span class="badge badge--trash">Papelera</span>
                                        <small>{{ $location->deleted_at?->diffForHumans() }}</small>
                                    @else
                                        <span class="badge {{ $location->is_active ? 'badge--success' : 'badge--muted' }}">
                                            {{ $location->is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    @endif
                                </td>
                                <td data-label="Acciones">
                                    @if ($status === 'trash')
                                        <div class="category-actions">
                                            <form method="post" action="{{ route('admin.locations.restore', $location->id) }}">
                                                @csrf
                                                <button class="category-action category-action--restore" type="submit">
                                                    <span aria-hidden="true">↻</span> Restaurar
                                                </button>
                                            </form>
                                            <form
                                                method="post"
                                                action="{{ route('admin.locations.force-destroy', $location->id) }}"
                                                data-confirm-delete="Esta ubicación será eliminada definitivamente."
                                                data-confirm-title="Eliminar ubicación definitivamente"
                                                data-confirm-name="{{ $location->name }}"
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
                                                data-location-edit
                                                data-location-id="{{ $location->id }}"
                                                data-location-payload="{{ json_encode($editPayload, JSON_UNESCAPED_UNICODE) }}"
                                            >
                                                <span aria-hidden="true">✎</span> Editar
                                            </button>
                                            <form
                                                method="post"
                                                action="{{ route('admin.locations.destroy', $location) }}"
                                                data-confirm-delete="La ubicación se enviará a la papelera."
                                                data-confirm-title="Eliminar ubicación"
                                                data-confirm-name="{{ $location->name }}"
                                                data-confirm-button="Enviar a la papelera"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="category-action category-action--delete"
                                                    type="submit"
                                                    @disabled($deleteProtected)
                                                    title="{{ $deleteProtection }}"
                                                >
                                                    <span aria-hidden="true">⌫</span> Eliminar
                                                </button>
                                            </form>
                                        </div>
                                        @if ($deleteProtected)
                                            <small class="location-delete-protection">{{ $deleteProtection }}</small>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="category-table__empty">
                                <td colspan="{{ $status === 'trash' ? 6 : 7 }}">No se encontraron ubicaciones.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-pagination">
                <p>Mostrando {{ $locations->firstItem() ?? 0 }}–{{ $locations->lastItem() ?? 0 }} de {{ $locations->total() }} ubicaciones</p>
                {{ $locations->onEachSide(1)->links() }}
            </div>
        </section>

        <dialog
            class="category-editor-dialog location-editor-dialog"
            data-location-create-dialog
            aria-labelledby="location-create-title"
        >
            <form
                method="post"
                action="{{ route('admin.locations.store') }}"
                class="category-editor-form"
                data-location-form
                data-location-options-url="{{ $locationOptionsUrl }}"
            >
                @csrf
                <header class="category-editor-dialog__header">
                    <div class="category-editor-dialog__identity">
                        <span class="category-editor-dialog__icon" aria-hidden="true">＋</span>
                        <div>
                            <span class="eyebrow">Cobertura geográfica</span>
                            <h2 id="location-create-title">Nueva ubicación</h2>
                        </div>
                    </div>
                    <button type="button" data-location-dialog-close aria-label="Cerrar">×</button>
                </header>
                <div class="category-editor-dialog__body">
                    @include('admin.taxonomy.partials.location-form-fields', ['mode' => 'create'])
                </div>
                <footer class="category-editor-dialog__footer">
                    <small>La jerarquía territorial será validada antes de guardar.</small>
                    <div>
                        <button class="button button--quiet" type="button" data-location-dialog-close>Cancelar</button>
                        <button class="button button--primary" type="submit">Crear ubicación</button>
                    </div>
                </footer>
            </form>
        </dialog>

        <dialog
            class="category-editor-dialog location-editor-dialog"
            data-location-edit-dialog
            aria-labelledby="location-edit-title"
        >
            <form
                method="post"
                action="{{ route('admin.locations.index') }}"
                class="category-editor-form"
                data-location-edit-form
                data-location-form
                data-location-options-url="{{ $locationOptionsUrl }}"
            >
                @csrf
                @method('PUT')
                <header class="category-editor-dialog__header">
                    <div class="category-editor-dialog__identity">
                        <span class="category-editor-dialog__icon category-editor-dialog__icon--edit" aria-hidden="true">✎</span>
                        <div>
                            <span class="eyebrow">Configuración territorial</span>
                            <h2 id="location-edit-title">Editar ubicación</h2>
                        </div>
                    </div>
                    <button type="button" data-location-dialog-close aria-label="Cerrar">×</button>
                </header>
                <div class="category-editor-dialog__body">
                    @include('admin.taxonomy.partials.location-form-fields', ['mode' => 'edit'])
                </div>
                <footer class="category-editor-dialog__footer">
                    <small data-location-edit-summary>Actualiza los datos y la jerarquía territorial.</small>
                    <div>
                        <button class="button button--quiet" type="button" data-location-dialog-close>Cancelar</button>
                        <button class="button button--primary category-save-button" type="submit">Guardar cambios</button>
                    </div>
                </footer>
            </form>
        </dialog>
    </div>
@endsection
