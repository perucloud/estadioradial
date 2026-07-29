@extends('layouts.admin')

@section('title', 'Ubicaciones')
@section('eyebrow', 'Cobertura geográfica')
@section('heading', 'Ubicaciones')

@section('content')
    <div class="taxonomy-metrics taxonomy-metrics--locations" aria-label="Resumen territorial">
        <article><span>Total</span><strong>{{ $stats['total'] }}</strong></article>
        <article><span>Países</span><strong>{{ $stats['countries'] }}</strong></article>
        <article><span>Regiones</span><strong>{{ $stats['regions'] }}</strong></article>
        <article><span>Provincias</span><strong>{{ $stats['provinces'] }}</strong></article>
        <article><span>Distritos</span><strong>{{ $stats['districts'] }}</strong></article>
        <article><span>Papelera</span><strong>{{ $stats['trash'] }}</strong></article>
    </div>

    <details class="panel taxonomy-create" @if ($errors->hasAny(['name', 'slug', 'type', 'parent_id', 'country_code'])) open @endif>
        <summary class="taxonomy-create__summary">
            <span>
                <span class="eyebrow">Nueva ubicación</span>
                <strong>Crear división territorial</strong>
            </span>
            <span class="button button--primary">+ Nueva ubicación</span>
        </summary>

        <form method="post" action="{{ route('admin.locations.store') }}" class="form-stack taxonomy-form" data-location-form>
            @csrf
            <div class="form-grid form-grid--three">
                <label>
                    Nombre
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="120" required>
                    @error('name') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Slug
                    <input type="text" name="slug" value="{{ old('slug') }}" maxlength="140" placeholder="Se genera automáticamente">
                    @error('slug') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Tipo
                    <select name="type" data-location-type required>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', 'country') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <small class="field-error">{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="form-grid form-grid--three">
                <label>
                    Ubicación superior
                    <select name="parent_id" data-location-parent>
                        <option value="">Nivel principal</option>
                        @foreach ($parentOptions as $option)
                            <option
                                value="{{ $option->id }}"
                                data-location-option-type="{{ $option->type }}"
                                @selected(old('parent_id') == $option->id)
                            >
                                {{ str_repeat('— ', $option->tree_depth) }}{{ $option->name }} · {{ $option->typeLabel() }}
                            </option>
                        @endforeach
                    </select>
                    <small class="field-help" data-location-parent-help>Los países se crean en el nivel principal.</small>
                    @error('parent_id') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Código de país
                    <input type="text" name="country_code" value="{{ old('country_code') }}" maxlength="2" placeholder="PE" data-country-code>
                    <small class="field-help">ISO de dos letras; obligatorio para países.</small>
                    @error('country_code') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Código UBIGEO
                    <input type="text" name="ubigeo" value="{{ old('ubigeo') }}" maxlength="12" placeholder="Opcional">
                    @error('ubigeo') <small class="field-error">{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="form-grid">
                <label>Latitud <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude') }}" min="-90" max="90" placeholder="Opcional"></label>
                <label>Longitud <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude') }}" min="-180" max="180" placeholder="Opcional"></label>
            </div>

            <label>Descripción <textarea name="description" rows="3" maxlength="1500">{{ old('description') }}</textarea></label>
            <div class="form-grid">
                <label>Título SEO <input type="text" name="seo_title" value="{{ old('seo_title') }}" maxlength="70" placeholder="Se completa desde el nombre"></label>
                <label>Descripción SEO <textarea name="seo_description" rows="2" maxlength="170" placeholder="Se completa desde la descripción">{{ old('seo_description') }}</textarea></label>
            </div>

            <input type="hidden" name="is_active" value="0">
            <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))><span>Ubicación activa</span></label>

            <div class="form-actions">
                <small class="field-help">La jerarquía territorial se valida antes de guardar.</small>
                <button class="button button--primary" type="submit">Crear ubicación</button>
            </div>
        </form>
    </details>

    <section class="panel taxonomy-toolbar">
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
                    @foreach ($parentOptions as $option)
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

    @if ($status !== 'trash')
        <div class="page-actions">
            <p>Ordena los territorios dentro de su nivel geográfico.</p>
            <button class="button button--primary" type="submit" form="location-order-form">Guardar orden</button>
        </div>
        <form id="location-order-form" method="post" action="{{ route('admin.locations.reorder') }}">@csrf</form>
    @endif

    <section class="panel table-panel">
        <div class="responsive-table">
            <table class="taxonomy-table location-table">
                <thead>
                    <tr>
                        @if ($status !== 'trash') <th>Orden</th> @endif
                        <th>Ubicación</th>
                        <th>Tipo</th>
                        <th>Códigos</th>
                        <th>Noticias</th>
                        <th>Estado</th>
                        <th><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody @if ($status !== 'trash') data-sortable-locations @endif>
                    @forelse ($locations as $location)
                        <tr @if ($status !== 'trash') draggable="true" data-location-row @endif>
                            @if ($status !== 'trash')
                                <td>
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
                                </td>
                            @endif
                            <td>
                                <div class="taxonomy-name" style="--tree-depth: {{ $location->tree_depth ?? 0 }}">
                                    <span class="location-type-icon" aria-hidden="true">
                                        {{ ['country' => '🌐', 'region' => '🗺️', 'province' => '📍', 'district' => '🏘️'][$location->type] }}
                                    </span>
                                    <span>
                                        <strong>{{ $location->name }}</strong>
                                        <small>{{ $location->slug }} · superior: {{ $location->parent?->name ?? 'ninguna' }}</small>
                                    </span>
                                </div>
                            </td>
                            <td><span class="location-type-badge location-type-badge--{{ $location->type }}">{{ $location->typeLabel() }}</span><small>{{ $location->children_count }} elementos hijos</small></td>
                            <td><strong>{{ $location->country_code ?? '—' }}</strong><small>UBIGEO: {{ $location->ubigeo ?? 'sin registrar' }}</small></td>
                            <td><strong>{{ $location->posts_count }}</strong><small>publicaciones</small></td>
                            <td>
                                @if ($status === 'trash')
                                    <span class="badge badge--trash">Papelera</span>
                                    <small>{{ $location->deleted_at?->diffForHumans() }}</small>
                                @else
                                    <span class="badge {{ $location->is_active ? 'badge--success' : 'badge--muted' }}">{{ $location->is_active ? 'Activa' : 'Inactiva' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($status === 'trash')
                                    <div class="taxonomy-row-actions">
                                        <form method="post" action="{{ route('admin.locations.restore', $location->id) }}">@csrf<button class="button button--quiet button--compact" type="submit">Restaurar</button></form>
                                        <form method="post" action="{{ route('admin.locations.force-destroy', $location->id) }}" onsubmit="return confirm('¿Eliminar definitivamente esta ubicación?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="danger-link" type="submit">Eliminar definitivamente</button>
                                        </form>
                                    </div>
                                @else
                                    <details class="row-editor">
                                        <summary>Editar</summary>
                                        <div class="row-editor__panel row-editor__panel--category">
                                            <form method="post" action="{{ route('admin.locations.update', $location) }}" class="form-stack form-stack--compact" data-location-form>
                                                @csrf
                                                @method('PUT')
                                                <div class="form-grid">
                                                    <label>Nombre <input type="text" name="name" value="{{ $location->name }}" maxlength="120" required></label>
                                                    <label>Slug <input type="text" name="slug" value="{{ $location->slug }}" maxlength="140" required></label>
                                                </div>
                                                <div class="form-grid">
                                                    <label>
                                                        Tipo
                                                        <select name="type" data-location-type>
                                                            @foreach ($types as $value => $label)
                                                                <option value="{{ $value }}" @selected($location->type === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label>
                                                        Ubicación superior
                                                        <select name="parent_id" data-location-parent>
                                                            <option value="">Nivel principal</option>
                                                            @foreach ($parentOptions as $option)
                                                                @if ($option->id !== $location->id)
                                                                    <option
                                                                        value="{{ $option->id }}"
                                                                        data-location-option-type="{{ $option->type }}"
                                                                        @selected($location->parent_id === $option->id)
                                                                    >{{ str_repeat('— ', $option->tree_depth) }}{{ $option->name }} · {{ $option->typeLabel() }}</option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                        <small class="field-help" data-location-parent-help></small>
                                                    </label>
                                                </div>
                                                <div class="form-grid">
                                                    <label>Código país <input type="text" name="country_code" value="{{ $location->country_code }}" maxlength="2" data-country-code></label>
                                                    <label>UBIGEO <input type="text" name="ubigeo" value="{{ $location->ubigeo }}" maxlength="12"></label>
                                                </div>
                                                <div class="form-grid">
                                                    <label>Latitud <input type="number" step="0.0000001" name="latitude" value="{{ $location->latitude }}"></label>
                                                    <label>Longitud <input type="number" step="0.0000001" name="longitude" value="{{ $location->longitude }}"></label>
                                                </div>
                                                <label>Descripción <textarea name="description" rows="2" maxlength="1500">{{ $location->description }}</textarea></label>
                                                <label>Título SEO <input type="text" name="seo_title" value="{{ $location->seo_title }}" maxlength="70"></label>
                                                <label>Descripción SEO <textarea name="seo_description" rows="2" maxlength="170">{{ $location->seo_description }}</textarea></label>
                                                <input type="hidden" name="is_active" value="0">
                                                <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked($location->is_active)><span>Ubicación activa</span></label>
                                                <button class="button button--primary" type="submit">Guardar cambios</button>
                                            </form>

                                            <form method="post" action="{{ route('admin.locations.destroy', $location) }}" onsubmit="return confirm('¿Enviar esta ubicación a la papelera?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="danger-link" type="submit" @disabled($location->children_count > 0 || $location->posts_count > 0)>
                                                    {{ $location->children_count > 0
                                                        ? 'Contiene divisiones territoriales'
                                                        : ($location->posts_count > 0 ? 'Ubicación usada por noticias' : 'Enviar a la papelera') }}
                                                </button>
                                            </form>
                                        </div>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $status === 'trash' ? 6 : 7 }}">No se encontraron ubicaciones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-pagination">
            <p>Mostrando {{ $locations->firstItem() ?? 0 }}–{{ $locations->lastItem() ?? 0 }} de {{ $locations->total() }} ubicaciones</p>
            {{ $locations->onEachSide(1)->links() }}
        </div>
    </section>
@endsection
