@extends('layouts.admin')

@section('title', 'Portada editorial')
@section('eyebrow', 'Apariencia y prioridades')
@section('heading', 'Portada editorial')

@php($manualIds = collect($hero['post_ids'] ?? [])->map(fn ($id) => (int) $id))

@section('content')
    <form method="post" action="{{ route('admin.appearance.homepage.update') }}" class="form-stack">
        @csrf
        @method('PUT')

        <div class="appearance-grid">
            <section class="panel form-stack">
                <div><span class="eyebrow">Carrusel principal</span><h2>Hero de noticias</h2></div>
                <div class="form-grid">
                    <label>Rotación
                        <select name="hero[mode]">
                            <option value="automatic" @selected($hero['mode'] === 'automatic')>Automática</option>
                            <option value="manual" @selected($hero['mode'] === 'manual')>Solo con controles</option>
                        </select>
                    </label>
                    <label>Intervalo en segundos
                        <input type="number" name="hero[interval_seconds]" value="{{ (int) round($hero['interval'] / 1000) }}" min="4" max="60" required>
                    </label>
                    <label>Efecto
                        <select name="hero[effect]">
                            <option value="parallax" @selected($hero['effect'] === 'parallax')>Parallax</option>
                            <option value="slide" @selected($hero['effect'] === 'slide')>Deslizamiento</option>
                            <option value="fade" @selected($hero['effect'] === 'fade')>Fundido</option>
                        </select>
                    </label>
                    <label>Cantidad de noticias
                        <input type="number" name="hero[news_limit]" value="{{ $hero['news_limit'] }}" min="4" max="8" required>
                    </label>
                    <label>Selección editorial
                        <select name="hero[selection_mode]">
                            <option value="automatic" @selected($hero['selection_mode'] === 'automatic')>Automática por últimas publicaciones</option>
                            <option value="manual" @selected($hero['selection_mode'] === 'manual')>Selección manual</option>
                        </select>
                    </label>
                </div>
                <label class="check-row"><input type="checkbox" name="hero[loop]" value="1" @checked($hero['loop'])><span>Repetir continuamente</span></label>
                <label class="check-row"><input type="checkbox" name="hero[parallax]" value="1" @checked($hero['parallax'])><span>Movimiento parallax con el puntero</span></label>
            </section>

            <section class="panel form-stack">
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
                <p class="panel-note">Hasta implementar métricas históricas, el periodo limita la fecha de publicación y el ranking utiliza las lecturas acumuladas.</p>
            </section>
        </div>

        <section class="panel">
            <div class="panel__header">
                <div><span class="eyebrow">Selección y prioridades</span><h2>Noticias de portada</h2></div>
                <span class="badge">{{ $posts->count() }} publicadas</span>
            </div>
            <div class="responsive-table">
                <table class="homepage-post-table">
                    <thead>
                        <tr>
                            <th>Noticia</th>
                            <th>Hero manual</th>
                            <th>Orden</th>
                            <th>Prioridad</th>
                            <th>Destacada</th>
                            <th>Ocultar</th>
                            <th>Fijar hasta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            @php($manualPosition = $manualIds->search($post->id))
                            <tr>
                                <td>
                                    <strong>{{ $post->title }}</strong>
                                    <small>{{ $post->category->name }} · {{ number_format($post->views_count) }} lecturas</small>
                                </td>
                                <td>
                                    <input type="hidden" name="hero_posts[{{ $post->id }}][selected]" value="0">
                                    <input type="checkbox" name="hero_posts[{{ $post->id }}][selected]" value="1" @checked($manualPosition !== false) aria-label="Seleccionar {{ $post->title }}">
                                </td>
                                <td><input class="order-input" type="number" name="hero_posts[{{ $post->id }}][order]" value="{{ $manualPosition === false ? 100 : $manualPosition + 1 }}" min="1" max="100"></td>
                                <td><input class="order-input" type="number" name="posts[{{ $post->id }}][editorial_priority]" value="{{ $post->editorial_priority }}" min="0" max="1000" required></td>
                                <td>
                                    <input type="hidden" name="posts[{{ $post->id }}][is_featured]" value="0">
                                    <input type="checkbox" name="posts[{{ $post->id }}][is_featured]" value="1" @checked($post->is_featured)>
                                </td>
                                <td>
                                    <input type="hidden" name="posts[{{ $post->id }}][is_homepage_hidden]" value="0">
                                    <input type="checkbox" name="posts[{{ $post->id }}][is_homepage_hidden]" value="1" @checked($post->is_homepage_hidden)>
                                </td>
                                <td><input type="datetime-local" name="posts[{{ $post->id }}][pinned_until]" value="{{ $post->pinned_until?->format('Y-m-d\TH:i') }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="sticky-save-bar">
            <a class="button button--quiet" href="{{ route('home') }}" target="_blank">Ver portada</a>
            <button class="button button--primary" type="submit">Guardar configuración</button>
        </div>
    </form>
@endsection
