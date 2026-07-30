@php($creating = $mode === 'create')

<input type="hidden" name="form_context" value="{{ $creating ? 'create' : 'edit' }}" data-location-context>

<div class="category-editor-form__grid location-editor-form__grid">
    <section class="category-editor-form__section">
        <div class="category-editor-form__section-heading">
            <span aria-hidden="true">⌖</span>
            <div>
                <strong>Identidad territorial</strong>
                <small>Nombre, nivel geográfico y ubicación superior.</small>
            </div>
        </div>

        <div class="form-grid">
            <label>
                Nombre
                <input
                    type="text"
                    name="name"
                    value="{{ $creating ? old('name') : '' }}"
                    maxlength="120"
                    required
                    data-slug-source
                >
                @if ($creating) @error('name') <small class="field-error">{{ $message }}</small> @enderror @endif
            </label>
            <label>
                Slug
                <input
                    type="text"
                    name="slug"
                    value="{{ $creating ? old('slug') : '' }}"
                    maxlength="140"
                    placeholder="Se genera automáticamente"
                    data-slug-target
                >
                @if ($creating) @error('slug') <small class="field-error">{{ $message }}</small> @enderror @endif
            </label>
        </div>

        <div class="form-grid">
            <label>
                Tipo
                <select name="type" data-location-type required>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected($creating && old('type', 'country') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if ($creating) @error('type') <small class="field-error">{{ $message }}</small> @enderror @endif
            </label>
            <label>
                Ubicación superior
                <select name="parent_id" data-location-parent>
                    <option value="">Nivel principal</option>
                    @if ($creating && $oldParent)
                        <option
                            value="{{ $oldParent->id }}"
                            data-location-option-type="{{ $oldParent->type }}"
                            selected
                        >
                            {{ $oldParent->name }} · {{ $oldParent->typeLabel() }}
                        </option>
                    @endif
                </select>
                <small class="field-help" data-location-parent-help>Los países se crean en el nivel principal.</small>
                @if ($creating) @error('parent_id') <small class="field-error">{{ $message }}</small> @enderror @endif
            </label>
        </div>

        <label>
            Descripción
            <textarea name="description" rows="4" maxlength="1500">{{ $creating ? old('description') : '' }}</textarea>
        </label>

        <div class="location-editor-active">
            <input type="hidden" name="is_active" value="0">
            <label class="check-row">
                <input type="checkbox" name="is_active" value="1" @checked($creating ? old('is_active', true) : false)>
                <span><strong>Ubicación activa</strong><small>Disponible como alcance geográfico de las noticias.</small></span>
            </label>
        </div>
    </section>

    <section class="category-editor-form__section">
        <div class="category-editor-form__section-heading">
            <span aria-hidden="true">#</span>
            <div>
                <strong>Códigos y coordenadas</strong>
                <small>Datos técnicos opcionales para identificar el territorio.</small>
            </div>
        </div>

        <div class="form-grid">
            <label>
                Código de país
                <input
                    type="text"
                    name="country_code"
                    value="{{ $creating ? old('country_code') : '' }}"
                    maxlength="2"
                    placeholder="PE"
                    data-country-code
                >
                <small class="field-help">Código ISO de dos letras, solo para países.</small>
                @if ($creating) @error('country_code') <small class="field-error">{{ $message }}</small> @enderror @endif
            </label>
            <label>
                Código UBIGEO
                <input type="text" name="ubigeo" value="{{ $creating ? old('ubigeo') : '' }}" maxlength="12" placeholder="Opcional">
                @if ($creating) @error('ubigeo') <small class="field-error">{{ $message }}</small> @enderror @endif
            </label>
        </div>

        <div class="form-grid">
            <label>
                Latitud
                <input type="number" step="0.0000001" name="latitude" value="{{ $creating ? old('latitude') : '' }}" min="-90" max="90" placeholder="Opcional">
            </label>
            <label>
                Longitud
                <input type="number" step="0.0000001" name="longitude" value="{{ $creating ? old('longitude') : '' }}" min="-180" max="180" placeholder="Opcional">
            </label>
        </div>

        <div class="category-editor-form__section-heading category-editor-form__section-heading--seo">
            <span aria-hidden="true">◎</span>
            <div>
                <strong>SEO territorial</strong>
                <small>Se completa automáticamente si se deja vacío.</small>
            </div>
        </div>

        <label>
            Título SEO
            <input type="text" name="seo_title" value="{{ $creating ? old('seo_title') : '' }}" maxlength="70" placeholder="Se completa desde el nombre">
        </label>
        <label>
            Descripción SEO
            <textarea name="seo_description" rows="3" maxlength="170" placeholder="Se completa desde la descripción">{{ $creating ? old('seo_description') : '' }}</textarea>
        </label>
    </section>
</div>
