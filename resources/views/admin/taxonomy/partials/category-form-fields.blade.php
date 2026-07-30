@php($creating = $mode === 'create')

<input type="hidden" name="form_context" value="{{ $creating ? 'create' : 'edit' }}" data-category-context>

<div class="category-editor-form__grid">
    <section class="category-editor-form__section">
        <div class="category-editor-form__section-heading">
            <span aria-hidden="true">✦</span>
            <div>
                <strong>Identidad editorial</strong>
                <small>Nombre, jerarquía y presentación pública.</small>
            </div>
        </div>

        <div class="form-grid">
            <label>
                Nombre
                <input
                    type="text"
                    name="name"
                    value="{{ $creating ? old('name') : '' }}"
                    maxlength="100"
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
                    maxlength="120"
                    placeholder="Se genera automáticamente"
                    data-slug-target
                >
                @if ($creating) @error('slug') <small class="field-error">{{ $message }}</small> @enderror @endif
            </label>
        </div>

        <label>
            Categoría superior
            <select name="parent_id" data-category-parent>
                <option value="">Categoría principal</option>
                @foreach ($parentOptions as $option)
                    <option value="{{ $option->id }}" @selected($creating && old('parent_id') == $option->id)>
                        {{ str_repeat('— ', $option->tree_depth) }}{{ $option->name }}
                    </option>
                @endforeach
            </select>
            @if ($creating) @error('parent_id') <small class="field-error">{{ $message }}</small> @enderror @endif
        </label>

        <label>
            Descripción
            <textarea name="description" rows="4" maxlength="1000">{{ $creating ? old('description') : '' }}</textarea>
        </label>

        <div class="form-grid form-grid--three">
            <label>
                Color
                <input type="color" name="color" value="{{ $creating ? old('color', '#d9251b') : '#d9251b' }}" required>
            </label>
            <label>
                Icono
                <input type="text" name="icon" value="{{ $creating ? old('icon') : '' }}" maxlength="100" placeholder="Opcional: 🏛️">
            </label>
            <label>
                Diseño
                <select name="homepage_layout">
                    @foreach (['standard' => 'Estándar', 'featured' => 'Destacada', 'grid' => 'Cuadrícula'] as $value => $label)
                        <option value="{{ $value }}" @selected($creating && old('homepage_layout', 'standard') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    <section class="category-editor-form__section">
        <div class="category-editor-form__section-heading">
            <span aria-hidden="true">⌁</span>
            <div>
                <strong>Portada y visibilidad</strong>
                <small>Prioridad y presencia dentro del portal.</small>
            </div>
        </div>

        <div class="form-grid">
            <label>
                Relevancia
                <input type="number" name="relevance_weight" value="{{ $creating ? old('relevance_weight', 50) : 50 }}" min="0" max="1000" required>
            </label>
            <label>
                Límite en portada
                <input type="number" name="homepage_limit" value="{{ $creating ? old('homepage_limit', 4) : 4 }}" min="1" max="12" required>
            </label>
        </div>

        <div class="category-editor-form__toggles">
            <input type="hidden" name="is_active" value="0">
            <label class="check-row">
                <input type="checkbox" name="is_active" value="1" @checked($creating ? old('is_active', true) : false)>
                <span><strong>Categoría activa</strong><small>Disponible para publicar noticias.</small></span>
            </label>
            <input type="hidden" name="show_in_menu" value="0">
            <label class="check-row">
                <input type="checkbox" name="show_in_menu" value="1" @checked($creating ? old('show_in_menu', true) : false)>
                <span><strong>Mostrar en menú</strong><small>Visible en la navegación pública.</small></span>
            </label>
            <input type="hidden" name="show_on_home" value="0">
            <label class="check-row">
                <input type="checkbox" name="show_on_home" value="1" @checked($creating ? old('show_on_home', true) : false)>
                <span><strong>Mostrar en portada</strong><small>Disponible para bloques del landing.</small></span>
            </label>
        </div>

        <div class="category-editor-form__section-heading category-editor-form__section-heading--seo">
            <span aria-hidden="true">◎</span>
            <div>
                <strong>SEO</strong>
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
