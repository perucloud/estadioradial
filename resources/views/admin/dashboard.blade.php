@extends('layouts.admin')

@section('title', 'Resumen')
@section('heading', 'Resumen del portal')

@section('content')
    <section class="metric-grid" aria-label="Indicadores principales">
        @foreach ($metrics as $label => $value)
            <article class="metric-card">
                <span>{{ $label }}</span>
                <strong>{{ number_format($value) }}</strong>
                <small>Registro actual</small>
            </article>
        @endforeach
    </section>

    <div class="dashboard-grid">
        <section class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Actividad editorial</span>
                    <h2>Noticias recientes</h2>
                </div>
                <span class="badge">Fase inicial</span>
            </div>

            @forelse ($recentPosts as $post)
                <div class="content-row">
                    <span>
                        <strong>{{ $post->title }}</strong>
                        <small>{{ $post->status }} · {{ $post->updated_at->diffForHumans() }}</small>
                    </span>
                    <span class="status-dot {{ $post->status === 'published' ? 'is-live' : '' }}"></span>
                </div>
            @empty
                <p class="empty-state">Todavía no hay noticias registradas.</p>
            @endforelse
        </section>

        <aside class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Estado operativo</span>
                    <h2>Servicios</h2>
                </div>
            </div>
            <div class="service-stat">
                <span>Señales activas</span>
                <strong>{{ $streams }}</strong>
            </div>
            <div class="service-stat">
                <span>Publicidad activa</span>
                <strong>{{ $advertisements }}</strong>
            </div>
            <div class="service-stat service-stat--scheduler">
                <span>
                    <strong>Publicaciones programadas</strong>
                    <small>
                        @if ($scheduler['last_completed_at'])
                            Última revisión:
                            {{ \Illuminate\Support\Carbon::parse($scheduler['last_completed_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}
                        @else
                            Todavía no se registra ninguna ejecución.
                        @endif
                    </small>
                </span>
                <span class="scheduler-status {{ $scheduler['active'] ? 'is-active' : 'is-inactive' }}">
                    {{ $scheduler['active'] ? 'Activo' : 'Detenido' }}
                </span>
            </div>
            <div class="service-stat">
                <span>Noticias vencidas pendientes</span>
                <strong class="{{ $overdueScheduledPosts > 0 ? 'text-danger' : '' }}">{{ $overdueScheduledPosts }}</strong>
            </div>
            @if ($scheduler['last_error'])
                <p class="panel-note panel-note--danger">{{ $scheduler['last_error'] }}</p>
            @else
                <p class="panel-note">
                    El programador revisa cada minuto y publica usando el horario
                    {{ config('app.timezone') }}.
                </p>
            @endif
        </aside>
    </div>

    <section
        class="panel dashboard-location-settings"
        data-post-location
        data-location-options-url="{{ $locationOptionsUrl }}"
    >
        <div class="panel__header">
            <div>
                <span class="eyebrow">Preferencia editorial</span>
                <h2>Alcance geográfico predeterminado</h2>
            </div>
            <span class="location-selection-status" data-post-location-status>
                {{ $defaultLocationLabel }}
            </span>
        </div>

        <p class="field-help">
            Esta ubicación se seleccionará automáticamente al crear una noticia nueva.
            El redactor podrá cambiarla o retirarla en cada publicación.
        </p>

        @if (auth()->user()->hasPermission('settings.manage'))
            <form method="post" action="{{ route('admin.dashboard.default-location.update') }}" class="dashboard-location-form">
                @csrf
                @method('PUT')

                <div class="post-location-grid">
                    <label>
                        País
                        <select name="default_location_country_id" data-post-location-level="country" required>
                            <option value="">Seleccionar país</option>
                            @foreach ($defaultLocationOptions->get('country', collect()) as $location)
                                <option value="{{ $location->id }}" @selected(($defaultLocationSelection['country'] ?? null) === $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        @error('default_location_country_id') <small class="field-error">{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Región
                        <select name="default_location_region_id" data-post-location-level="region">
                            <option value="">Toda la nación</option>
                            @foreach ($defaultLocationOptions->get('region', collect()) as $location)
                                <option value="{{ $location->id }}" @selected(($defaultLocationSelection['region'] ?? null) === $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        @error('default_location_region_id') <small class="field-error">{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Provincia
                        <select name="default_location_province_id" data-post-location-level="province">
                            <option value="">Toda la región</option>
                            @foreach ($defaultLocationOptions->get('province', collect()) as $location)
                                <option value="{{ $location->id }}" @selected(($defaultLocationSelection['province'] ?? null) === $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        @error('default_location_province_id') <small class="field-error">{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Distrito
                        <select name="default_location_district_id" data-post-location-level="district">
                            <option value="">Toda la provincia</option>
                            @foreach ($defaultLocationOptions->get('district', collect()) as $location)
                                <option value="{{ $location->id }}" @selected(($defaultLocationSelection['district'] ?? null) === $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        @error('default_location_district_id') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="editorial-territory-settings">
                    <div class="editorial-territory-settings__copy">
                        <span class="eyebrow">Identidad pública</span>
                        <h3>Badge territorial de las noticias</h3>
                        <p>
                            Se mostrará junto a cualquier categoría temática. Si dejas el texto vacío,
                            se construirá automáticamente con el distrito y la región seleccionados.
                        </p>
                    </div>
                    <div class="editorial-territory-settings__controls">
                        <label class="check-row">
                            <input type="hidden" name="editorial_badge_enabled" value="0">
                            <input
                                type="checkbox"
                                name="editorial_badge_enabled"
                                value="1"
                                data-editorial-badge-enabled
                                @checked(old('editorial_badge_enabled', $editorialIdentity['enabled']))
                            >
                            <span>Mostrar badge territorial</span>
                        </label>
                        <label>
                            Texto personalizado
                            <input
                                type="text"
                                name="editorial_badge_label"
                                value="{{ old('editorial_badge_label', $editorialIdentity['custom_label']) }}"
                                maxlength="60"
                                placeholder="{{ $editorialIdentity['automatic_label'] ?: 'Juliaca · Puno' }}"
                                data-editorial-badge-custom
                            >
                            @error('editorial_badge_label') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <div
                        class="editorial-territory-settings__preview"
                        data-editorial-badge-preview
                        aria-label="Vista previa del badge"
                        @unless(old('editorial_badge_enabled', $editorialIdentity['enabled'])) hidden @endunless
                    >
                        <small>Vista previa</small>
                        <span>
                            <i aria-hidden="true">⌖</i>
                            <b data-editorial-badge-label>
                                {{ old('editorial_badge_label') ?: ($editorialIdentity['automatic_label'] ?: 'Juliaca · Puno') }}
                            </b>
                        </span>
                    </div>
                </div>

                <div class="dashboard-location-form__footer">
                    <small>Predeterminado inicial: Perú → Puno → San Román → Juliaca</small>
                    <button class="button button--primary" type="submit">Guardar ubicación predeterminada</button>
                </div>
            </form>
        @else
            <p class="panel-note">Solo un usuario con permisos de configuración puede modificar esta preferencia.</p>
        @endif
    </section>
@endsection
