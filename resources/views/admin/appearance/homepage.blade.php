@extends('layouts.admin')

@section('title', 'Portada editorial')
@section('eyebrow', 'Apariencia y prioridades')
@section('heading', 'Portada editorial')

@php
    $selectedCategoryIds = collect(old('hero.category_ids', $hero['category_ids'] ?? []))
        ->map(fn ($id) => (int) $id);
    $nationalCategoryIds = collect(old('national.category_ids', $national['category_ids'] ?? []))
        ->map(fn ($id) => (int) $id);
    $regionalCategoryIds = collect(old('regional.category_ids', $regional['category_ids'] ?? []))
        ->map(fn ($id) => (int) $id);
    $heroValues = array_replace($hero, old('hero', []));
    $nationalValues = array_replace($national, old('national', []));
    $regionalValues = array_replace($regional, old('regional', []));
@endphp

@section('content')
    <form
        method="post"
        action="{{ route('admin.appearance.homepage.update') }}"
        class="form-stack homepage-settings"
        data-homepage-settings
        data-hero-presets='@json($heroPresets)'
        data-error-tab="{{ $errors->has('national.*') ? 'national' : ($errors->has('regional.*') ? 'regional' : ($errors->has('slider.*') ? 'trends' : 'hero')) }}"
    >
        @csrf
        @method('PUT')

        <nav class="appearance-tabs" aria-label="Secciones de configuración de portada" role="tablist">
            <button type="button" class="is-active" role="tab" aria-selected="true" data-appearance-tab="hero">
                <span>01</span> Hero de noticias
            </button>
            <button type="button" role="tab" aria-selected="false" data-appearance-tab="trends">
                <span>02</span> Tendencias
            </button>
            <button type="button" role="tab" aria-selected="false" data-appearance-tab="national">
                <span>03</span> Noticias nacionales
            </button>
            <button type="button" role="tab" aria-selected="false" data-appearance-tab="regional">
                <span>04</span> Regionales
            </button>
        </nav>

        <section class="appearance-tab-panel is-active" role="tabpanel" data-appearance-panel="hero">
            <div class="panel hero-settings-panel form-stack">
                <div class="panel__header">
                    <div>
                        <span class="eyebrow">Carrusel principal</span>
                        <h2>Hero de noticias</h2>
                        <p class="panel-note">Define qué noticias se muestran y cómo se presenta el carrusel público.</p>
                    </div>
                    <span class="preset-status" data-preset-status>Modo {{ ucfirst($heroValues['preset_mode']) }}</span>
                </div>

                <div class="hero-settings-section">
                    <div class="hero-settings-section__heading">
                        <span>Configuración principal</span>
                        <small>Los cambios se guardan únicamente al confirmar el formulario.</small>
                    </div>
                    <div class="form-grid form-grid--three">
                        <label>Modo del Hero
                            <select name="hero[preset_mode]" data-hero-preset>
                                <option value="elegant" @selected($heroValues['preset_mode'] === 'elegant')>Elegante — Recomendado</option>
                                <option value="dynamic" @selected($heroValues['preset_mode'] === 'dynamic')>Dinámico</option>
                                <option value="cinematic" @selected($heroValues['preset_mode'] === 'cinematic')>Cinematográfico</option>
                                <option value="minimal" @selected($heroValues['preset_mode'] === 'minimal')>Minimalista</option>
                                <option value="custom" @selected($heroValues['preset_mode'] === 'custom')>Personalizado</option>
                            </select>
                            <small>Un modo aplica valores recomendados que después puedes modificar.</small>
                        </label>
                        <label>Rotación
                            <select name="hero[mode]" data-hero-setting>
                                <option value="automatic" @selected($heroValues['mode'] === 'automatic')>Automática</option>
                                <option value="manual" @selected($heroValues['mode'] === 'manual')>Solo con controles</option>
                            </select>
                        </label>
                        <input type="hidden" name="hero[selection_mode]" value="automatic">
                        <label>Orden de publicación
                            <select name="hero[sort_order]">
                                <option value="latest" @selected($heroValues['sort_order'] === 'latest')>Más recientes primero</option>
                                <option value="oldest" @selected($heroValues['sort_order'] === 'oldest')>Más antiguas primero</option>
                            </select>
                            <small>“Más recientes” ordena desde la última publicación hacia la más antigua.</small>
                        </label>
                        <label>Tipo de cantidad
                            <select name="hero[quantity_mode]" data-hero-setting data-quantity-mode>
                                <option value="specific" @selected($heroValues['quantity_mode'] === 'specific')>Cantidad específica</option>
                                <option value="all" @selected($heroValues['quantity_mode'] === 'all')>Todas las noticias disponibles</option>
                            </select>
                        </label>
                        <label data-news-limit>Cantidad de noticias
                            <input type="number" name="hero[news_limit]" value="{{ old('hero.news_limit', $heroValues['news_limit']) }}" min="1" step="1" data-hero-setting>
                            <small>Solo se aceptan enteros positivos.</small>
                        </label>
                        <label>Efecto general
                            <select name="hero[effect]" data-hero-setting>
                                @foreach ([
                                    'fade' => 'Fade — Recomendado',
                                    'slide-horizontal' => 'Slide horizontal',
                                    'slide-vertical' => 'Slide vertical',
                                    'push' => 'Push',
                                    'zoom-in' => 'Zoom In',
                                    'zoom-out' => 'Zoom Out',
                                    'ken-burns' => 'Ken Burns',
                                    'scale-fade' => 'Scale + Fade',
                                    'parallax' => 'Parallax',
                                    'blur' => 'Blur Transition',
                                    'cards-stack' => 'Cards Stack',
                                    'cinematic' => 'Cinematic',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected($heroValues['effect'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small>Controla la transición entre una noticia y la siguiente.</small>
                        </label>
                    </div>
                </div>

                <div class="hero-settings-section" data-category-settings>
                    <div class="hero-settings-section__heading">
                        <span>Categorías del Hero</span>
                        <small>El filtro se aplica también a la selección automática.</small>
                    </div>
                    <div class="form-grid">
                        <label>Origen de categorías
                            <select name="hero[category_mode]" data-hero-setting data-category-mode>
                                <option value="all" @selected($heroValues['category_mode'] === 'all')>Todas las categorías</option>
                                <option value="selected" @selected($heroValues['category_mode'] === 'selected')>Categorías seleccionadas</option>
                            </select>
                        </label>
                    </div>
                    <div class="category-multiselect" data-category-selector>
                        @foreach ($categories as $category)
                            <label style="--category-color: {{ $category->color }}">
                                <input
                                    type="checkbox"
                                    name="hero[category_ids][]"
                                    value="{{ $category->id }}"
                                    @checked($selectedCategoryIds->contains($category->id))
                                    data-hero-setting
                                >
                                <span>{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <details class="advanced-settings" data-advanced-settings @if($errors->has('hero.*')) open @endif>
                    <summary>
                        <span>
                            <strong>Configuración avanzada</strong>
                            <small>Animación, rendimiento, controles y accesibilidad</small>
                        </span>
                        <span aria-hidden="true">＋</span>
                    </summary>
                    <div class="advanced-settings__body form-stack">
                        <div class="form-grid form-grid--three">
                            <label>Animación de imagen
                                <select name="hero[image_animation]" data-hero-setting data-image-animation>
                                    @foreach ([
                                        'none' => 'Ninguna',
                                        'ken-burns' => 'Ken Burns',
                                        'zoom-in' => 'Zoom In',
                                        'zoom-out' => 'Zoom Out',
                                        'parallax' => 'Parallax',
                                        'move-horizontal' => 'Movimiento horizontal',
                                        'move-vertical' => 'Movimiento vertical',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected($heroValues['image_animation'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Intensidad de imagen
                                <select name="hero[image_intensity]" data-hero-setting>
                                    <option value="soft" @selected($heroValues['image_intensity'] === 'soft')>Suave</option>
                                    <option value="medium" @selected($heroValues['image_intensity'] === 'medium')>Media</option>
                                    <option value="high" @selected($heroValues['image_intensity'] === 'high')>Alta</option>
                                    <option value="soft-slow" @selected($heroValues['image_intensity'] === 'soft-slow')>Suave y lenta</option>
                                </select>
                            </label>
                            <label>Animación del contenido
                                <select name="hero[content_animation]" data-hero-setting>
                                    @foreach ([
                                        'none' => 'Ninguna',
                                        'fade' => 'Fade',
                                        'fade-up' => 'Fade Up',
                                        'fade-down' => 'Fade Down',
                                        'slide-left' => 'Slide Left',
                                        'slide-right' => 'Slide Right',
                                        'zoom' => 'Zoom',
                                        'blur' => 'Blur',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected($heroValues['content_animation'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Duración de transición
                                <div class="input-with-unit">
                                    <input type="number" name="hero[transition_duration]" value="{{ $heroValues['transition_duration'] }}" min="300" max="1500" step="50" data-hero-setting>
                                    <span>ms</span>
                                </div>
                            </label>
                            <label>Intervalo entre noticias
                                <div class="input-with-unit">
                                    <input type="number" name="hero[interval_seconds]" value="{{ (int) round($heroValues['interval'] / 1000) }}" min="3" max="20" data-hero-setting data-interval-seconds>
                                    <span>seg.</span>
                                </div>
                            </label>
                            <label>Oscurecimiento de imagen
                                <div class="input-with-unit">
                                    <input type="number" name="hero[overlay_opacity]" value="{{ $heroValues['overlay_opacity'] }}" min="0" max="60" data-hero-setting>
                                    <span>%</span>
                                </div>
                            </label>
                        </div>

                        <div class="advanced-switches">
                            @foreach ([
                                'preload_images' => 'Precargar la siguiente imagen',
                                'pause_on_hover' => 'Pausar al pasar el mouse',
                                'swipe' => 'Permitir Swipe táctil',
                                'lazy_load' => 'Activar Lazy Loading',
                                'animate_when_visible' => 'Animar solo cuando el Hero esté visible',
                                'show_arrows' => 'Mostrar flechas',
                                'show_indicators' => 'Mostrar indicadores',
                                'loop' => 'Repetir continuamente',
                                'pause_when_hidden' => 'Pausar si la pestaña no está activa',
                                'reset_after_manual' => 'Reiniciar temporizador tras navegación manual',
                                'reduce_motion_mobile' => 'Reducir animaciones en dispositivos móviles',
                            ] as $name => $label)
                                <label class="setting-switch">
                                    <input type="hidden" name="hero[{{ $name }}]" value="0">
                                    <input type="checkbox" name="hero[{{ $name }}]" value="1" @checked($heroValues[$name]) data-hero-setting>
                                    <span aria-hidden="true"></span>
                                    <strong>{{ $label }}</strong>
                                </label>
                            @endforeach
                            <label class="setting-switch" data-parallax-pointer>
                                <input type="hidden" name="hero[parallax]" value="0">
                                <input type="checkbox" name="hero[parallax]" value="1" @checked($heroValues['parallax']) data-hero-setting>
                                <span aria-hidden="true"></span>
                                <strong>Movimiento Parallax con el puntero</strong>
                            </label>
                        </div>
                    </div>
                </details>
            </div>
        </section>

        <section class="appearance-tab-panel" role="tabpanel" data-appearance-panel="trends" hidden>
            <div class="panel form-stack">
                <div><span class="eyebrow">Tendencias</span><h2>Slider de noticias más vistas</h2></div>
                <div class="form-grid">
                    <label>Movimiento
                        <select name="slider[mode]">
                            <option value="automatic" @selected($slider['mode'] === 'automatic')>Automático</option>
                            <option value="manual" @selected($slider['mode'] === 'manual')>Manual</option>
                        </select>
                    </label>
                    <label>Intervalo en segundos
                        <input type="number" name="slider[interval_seconds]" value="{{ (int) round($slider['interval'] / 1000) }}" min="3" max="60" required>
                    </label>
                    <label>Cantidad de noticias
                        <input type="number" name="slider[news_limit]" value="{{ $slider['news_limit'] }}" min="4" max="12" required>
                    </label>
                    <label>Periodo de publicaciones
                        <select name="slider[period_days]">
                            @foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días', 365 => '1 año', 0 => 'Todo el historial'] as $days => $label)
                                <option value="{{ $days }}" @selected((int) $slider['period_days'] === $days)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <label class="check-row"><input type="checkbox" name="slider[loop]" value="1" @checked($slider['loop'])><span>Repetir continuamente</span></label>
                <p class="panel-note">El periodo limita la fecha de publicación y el ranking utiliza las lecturas acumuladas.</p>
            </div>
        </section>

        <section class="appearance-tab-panel" role="tabpanel" data-appearance-panel="national" hidden>
            <div class="panel national-settings-panel form-stack">
                <div class="panel__header">
                    <div>
                        <span class="eyebrow">Información de nuestro país</span>
                        <h2>Noticias Nacionales</h2>
                        <p class="panel-note">Define el contenido nacional sin convertir esta sección en otro filtro regional.</p>
                    </div>
                    <label class="setting-switch setting-switch--compact">
                        <input type="hidden" name="national[enabled]" value="0">
                        <input type="checkbox" name="national[enabled]" value="1" @checked($nationalValues['enabled'])>
                        <span aria-hidden="true"></span>
                        <strong>Mostrar sección</strong>
                    </label>
                </div>

                <div class="national-country-summary">
                    <span class="national-country-summary__flag" aria-hidden="true">PE</span>
                    <div>
                        <small>País configurado</small>
                        <strong>{{ $nationalCountry?->name ?? 'Perú' }}</strong>
                        <p>La ubicación nacional se obtiene de la configuración general del portal.</p>
                    </div>
                    <span class="national-country-summary__status">Cobertura nacional</span>
                </div>

                <div class="hero-settings-section">
                    <div class="hero-settings-section__heading">
                        <span>Contenido editorial</span>
                        <small>Selecciona las categorías y el orden de aparición.</small>
                    </div>
                    <div class="form-grid">
                        <label>Categorías
                            <select name="national[category_mode]" data-national-category-mode>
                                <option value="all" @selected($nationalValues['category_mode'] === 'all')>Todas las categorías</option>
                                <option value="selected" @selected($nationalValues['category_mode'] === 'selected')>Categorías seleccionadas</option>
                            </select>
                        </label>
                        <label>Orden de publicación
                            <select name="national[sort_order]">
                                <option value="latest" @selected($nationalValues['sort_order'] === 'latest')>Más recientes primero</option>
                                <option value="oldest" @selected($nationalValues['sort_order'] === 'oldest')>Más antiguas primero</option>
                            </select>
                            <small>Utiliza la fecha real de publicación.</small>
                        </label>
                    </div>
                    <div class="category-multiselect" data-national-category-selector>
                        @foreach ($categories as $category)
                            <label style="--category-color: {{ $category->color }}">
                                <input
                                    type="checkbox"
                                    name="national[category_ids][]"
                                    value="{{ $category->id }}"
                                    @checked($nationalCategoryIds->contains($category->id))
                                >
                                <span>{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="hero-settings-section">
                    <div class="hero-settings-section__heading">
                        <span>Cobertura de las noticias</span>
                        <small>Determina qué publicaciones peruanas pueden entrar al módulo.</small>
                    </div>
                    <div class="form-grid">
                        <label>Regla de cobertura
                            <select name="national[coverage_mode]">
                                <option value="national_only" @selected($nationalValues['coverage_mode'] === 'national_only')>Solo alcance nacional — Recomendado</option>
                                <option value="all_peru" @selected($nationalValues['coverage_mode'] === 'all_peru')>Todas las noticias del Perú</option>
                            </select>
                            <small>“Solo alcance nacional” incluye publicaciones marcadas en Perú o sin ubicación específica.</small>
                        </label>
                        <label>Cantidad de noticias
                            <input type="number" name="national[news_limit]" value="{{ $nationalValues['news_limit'] }}" min="2" max="5" required>
                            <small>Entre 2 y 5 noticias en la portada.</small>
                        </label>
                    </div>
                    <div class="advanced-switches">
                        <label class="setting-switch">
                            <input type="hidden" name="national[exclude_regional_duplicates]" value="0">
                            <input
                                type="checkbox"
                                name="national[exclude_regional_duplicates]"
                                value="1"
                                @checked($nationalValues['exclude_regional_duplicates'])
                            >
                            <span aria-hidden="true"></span>
                            <strong>Excluir noticias ya mostradas en Regionales</strong>
                        </label>
                    </div>
                    <div class="national-rule-preview">
                        <span>Ejemplo editorial</span>
                        <div>
                            <strong>Política</strong>
                            <i>+</i>
                            <strong>{{ $nationalCountry?->name ?? 'Perú' }}</strong>
                            <i>→</i>
                            <b>Noticias Nacionales</b>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="appearance-tab-panel" role="tabpanel" data-appearance-panel="regional" hidden>
            <div class="panel regional-settings-panel form-stack">
                <div class="panel__header">
                    <div>
                        <span class="eyebrow">Información de nuestra región</span>
                        <h2>Noticias Regionales</h2>
                        <p class="panel-note">Controla el contenido, alcance territorial y paginación del módulo público.</p>
                    </div>
                    <label class="setting-switch setting-switch--compact">
                        <input type="hidden" name="regional[enabled]" value="0">
                        <input type="checkbox" name="regional[enabled]" value="1" @checked($regionalValues['enabled'])>
                        <span aria-hidden="true"></span>
                        <strong>Mostrar sección</strong>
                    </label>
                </div>

                <div class="hero-settings-section">
                    <div class="hero-settings-section__heading">
                        <span>Contenido editorial</span>
                        <small>Selecciona las categorías y el orden de aparición.</small>
                    </div>
                    <div class="form-grid">
                        <label>Categorías
                            <select name="regional[category_mode]" data-regional-category-mode>
                                <option value="all" @selected($regionalValues['category_mode'] === 'all')>Todas las categorías</option>
                                <option value="selected" @selected($regionalValues['category_mode'] === 'selected')>Categorías seleccionadas</option>
                            </select>
                        </label>
                        <label>Orden de publicación
                            <select name="regional[sort_order]">
                                <option value="latest" @selected($regionalValues['sort_order'] === 'latest')>Más recientes primero</option>
                                <option value="oldest" @selected($regionalValues['sort_order'] === 'oldest')>Más antiguas primero</option>
                            </select>
                            <small>Ordena utilizando la fecha real de publicación.</small>
                        </label>
                    </div>
                    <div class="category-multiselect" data-regional-category-selector>
                        @foreach ($categories as $category)
                            <label style="--category-color: {{ $category->color }}">
                                <input
                                    type="checkbox"
                                    name="regional[category_ids][]"
                                    value="{{ $category->id }}"
                                    @checked($regionalCategoryIds->contains($category->id))
                                >
                                <span>{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div
                    class="hero-settings-section regional-location-settings"
                    data-regional-location
                    data-location-options-url="{{ $locationOptionsUrl }}"
                >
                    <div class="hero-settings-section__heading">
                        <span>Alcance geográfico</span>
                        <small>Puedes detener el filtro en región, provincia o distrito.</small>
                    </div>
                    <div class="form-grid form-grid--three">
                        <label>Región
                            <select name="regional[region_id]" data-regional-location-level="region">
                                <option value="">Todas las regiones</option>
                                @foreach ($regionalRegions as $region)
                                    <option value="{{ $region->id }}" @selected((int) $regionalValues['region_id'] === $region->id)>{{ $region->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Provincia
                            <select
                                name="regional[province_id]"
                                data-regional-location-level="province"
                                @disabled(!$regionalValues['region_id'])
                            >
                                <option value="">Todas las provincias</option>
                                @foreach ($regionalProvinces as $province)
                                    <option value="{{ $province->id }}" @selected((int) $regionalValues['province_id'] === $province->id)>{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Distrito
                            <select
                                name="regional[district_id]"
                                data-regional-location-level="district"
                                @disabled(!$regionalValues['province_id'])
                            >
                                <option value="">Todos los distritos</option>
                                @foreach ($regionalDistricts as $district)
                                    <option value="{{ $district->id }}" @selected((int) $regionalValues['district_id'] === $district->id)>{{ $district->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="regional-location-summary">
                        <span>Filtro actual</span>
                        <strong data-regional-location-summary>Todas las regiones</strong>
                    </div>
                </div>

                <div class="hero-settings-section">
                    <div class="hero-settings-section__heading">
                        <span>Paginación</span>
                        <small>Divide las noticias regionales en páginas dentro del landing.</small>
                    </div>
                    <div class="form-grid">
                        <label>Noticias por página
                            <input type="number" name="regional[per_page]" value="{{ $regionalValues['per_page'] }}" min="2" max="12" required>
                            <small>Entre 2 y 12 noticias por página.</small>
                        </label>
                    </div>
                    <div class="advanced-switches">
                        <label class="setting-switch">
                            <input type="hidden" name="regional[pagination_enabled]" value="0">
                            <input type="checkbox" name="regional[pagination_enabled]" value="1" @checked($regionalValues['pagination_enabled']) data-regional-pagination>
                            <span aria-hidden="true"></span>
                            <strong>Activar paginación</strong>
                        </label>
                        <label class="setting-switch" data-regional-page-numbers>
                            <input type="hidden" name="regional[show_page_numbers]" value="0">
                            <input type="checkbox" name="regional[show_page_numbers]" value="1" @checked($regionalValues['show_page_numbers'])>
                            <span aria-hidden="true"></span>
                            <strong>Mostrar números de página</strong>
                        </label>
                    </div>
                </div>

                <div class="hero-settings-section">
                    <div class="hero-settings-section__heading">
                        <span>Badges territoriales</span>
                        <small>Destaca visualmente la procedencia de cada publicación.</small>
                    </div>
                    <div class="advanced-switches">
                        <label class="setting-switch">
                            <input type="hidden" name="regional[highlight_province]" value="0">
                            <input type="checkbox" name="regional[highlight_province]" value="1" @checked($regionalValues['highlight_province'])>
                            <span aria-hidden="true"></span>
                            <strong>Resaltar provincia</strong>
                        </label>
                        <label class="setting-switch">
                            <input type="hidden" name="regional[highlight_district]" value="0">
                            <input type="checkbox" name="regional[highlight_district]" value="1" @checked($regionalValues['highlight_district'])>
                            <span aria-hidden="true"></span>
                            <strong>Resaltar distrito</strong>
                        </label>
                    </div>
                    <div class="regional-badge-preview" aria-label="Vista previa de badges">
                        <span class="regional-badge-preview__province"><i>P</i> San Román</span>
                        <span class="regional-badge-preview__district"><i>D</i> Juliaca</span>
                        <small>Vista previa referencial</small>
                    </div>
                </div>
            </div>
        </section>

        <div class="sticky-save-bar">
            <a class="button button--quiet" href="{{ route('home') }}" target="_blank">Ver portada</a>
            <button class="button button--primary" type="submit">Guardar configuración</button>
        </div>
    </form>
@endsection
