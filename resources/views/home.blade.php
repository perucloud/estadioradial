@extends('layouts.app')

@section('title', 'Estación Radial | Noticias y radio en vivo')

@section('content')
    <section class="hero">
        <div class="container">
            @if ($featuredPosts->isNotEmpty())
                <a class="hero-banner" href="{{ route('programs.index') }}" aria-label="Conoce nuestra programación">
                    <img src="/images/demo/banner-radio.svg" alt="Estación Radial, voces que conectan">
                </a>

                <section
                    class="hero-rotator"
                    data-hero-rotator
                    data-hero-mode="{{ $heroSettings['mode'] }}"
                    data-hero-interval="{{ $heroSettings['interval'] }}"
                    data-hero-loop="{{ $heroSettings['loop'] ? 'true' : 'false' }}"
                    data-hero-effect="{{ $heroSettings['effect'] }}"
                    data-hero-parallax="{{ $heroSettings['parallax'] ? 'true' : 'false' }}"
                    aria-label="Noticias principales"
                    aria-roledescription="carrusel"
                >
                    <div class="hero-rotator__stage">
                        @foreach ($featuredPosts as $leadPost)
                            @php
                                $offsets = $featuredPosts->count() > 1
                                    ? range(1, min(3, $featuredPosts->count() - 1))
                                    : [];
                                $secondaryPosts = collect($offsets)
                                    ->map(fn ($offset) => $featuredPosts[($loop->index + $offset) % $featuredPosts->count()]);
                            @endphp
                            <x-hero-news-slide
                                :lead-post="$leadPost"
                                :secondary-posts="$secondaryPosts"
                                :is-first="$loop->first"
                                :position="$loop->iteration"
                            />
                        @endforeach
                    </div>

                    @if ($featuredPosts->count() > 1)
                        <button class="hero-rotator__arrow hero-rotator__arrow--previous" type="button" data-hero-prev aria-label="Noticia anterior">←</button>
                        <button class="hero-rotator__arrow hero-rotator__arrow--next" type="button" data-hero-next aria-label="Noticia siguiente">→</button>

                        <div class="hero-rotator__navigation">
                            <div class="hero-rotator__dots" aria-label="Seleccionar noticia principal">
                                @foreach ($featuredPosts as $post)
                                    <button
                                        type="button"
                                        class="{{ $loop->first ? 'is-active' : '' }}"
                                        data-hero-dot="{{ $loop->index }}"
                                        aria-label="Mostrar noticia {{ $loop->iteration }}"
                                        aria-controls="hero-news-slide-{{ $loop->iteration }}"
                                        aria-current="{{ $loop->first ? 'true' : 'false' }}"
                                    ></button>
                                @endforeach
                            </div>
                            <button class="hero-rotator__pause" type="button" data-hero-pause aria-label="Pausar rotación automática" aria-pressed="false">
                                <span aria-hidden="true">Ⅱ</span>
                            </button>
                        </div>
                        <p class="sr-only" data-hero-status aria-live="polite">Noticia 1 de {{ $featuredPosts->count() }}</p>
                    @endif
                </section>
            @endif
        </div>
    </section>

    <section class="on-air" aria-labelledby="on-air-title">
        <div class="container on-air__grid">
            <div class="on-air__intro">
                <span class="signal-mark" aria-hidden="true"><i></i><i></i><i></i></span>
                <div>
                    <span class="eyebrow">Ahora en vivo</span>
                    <h2 id="on-air-title">{{ $currentSchedule?->program->title ?? 'Programación continua' }}</h2>
                </div>
            </div>
            <div class="on-air__slot">
                <span>Estás escuchando</span>
                <strong>{{ $currentSchedule?->program->title ?? 'Señal principal' }}</strong>
                <small>{{ $currentSchedule ? substr($currentSchedule->starts_at, 0, 5).' - '.substr($currentSchedule->ends_at, 0, 5) : '24 horas' }}</small>
            </div>
            <div class="on-air__slot">
                <span>A continuación</span>
                <strong>{{ $nextSchedule?->program->title ?? 'Más música y noticias' }}</strong>
                <small>{{ $nextSchedule ? substr($nextSchedule->starts_at, 0, 5) : 'Consulta el horario' }}</small>
            </div>
            <a class="button button--primary" href="{{ route('live') }}">Abrir señal</a>
        </div>
    </section>

    <section class="section latest-news" aria-labelledby="regional-news-title">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Información de nuestra región</span>
                    <h2 id="regional-news-title">Noticias Regionales</h2>
                </div>
                <a
                    class="text-link"
                    href="{{ route('posts.locations.index') }}"
                >Ver todas las noticias regionales →</a>
            </div>

            @if ($regionalPosts->isNotEmpty())
                @php($regionalLead = $regionalPosts->first())

                <div class="latest-layout">
                    <article class="latest-lead">
                        <a class="latest-lead__image" href="{{ route('posts.show', [$regionalLead->category, $regionalLead]) }}">
                            <img src="{{ $regionalLead->coverUrl('card') }}" alt="{{ $regionalLead->media?->alt_text ?? '' }}" loading="lazy">
                            <span class="latest-lead__status">Último minuto</span>
                        </a>
                        <div class="latest-lead__body">
                            <a
                                class="category-pill"
                                style="--category-color: {{ $regionalLead->category->color }}"
                                href="{{ route('posts.category', $regionalLead->category) }}"
                            >{{ $regionalLead->category->name }}</a>
                            <a class="location-link" href="{{ $regionalLead->location->publicUrl() }}">
                                <span aria-hidden="true">⌖</span> {{ $regionalLead->location->name }}
                            </a>
                            <h3>
                                <a href="{{ route('posts.show', [$regionalLead->category, $regionalLead]) }}">
                                    {{ $regionalLead->title }}
                                </a>
                            </h3>
                            <p>{{ $regionalLead->excerpt }}</p>
                            <div class="editorial-meta">
                                <time datetime="{{ $regionalLead->published_at->toIso8601String() }}">
                                    {{ $regionalLead->published_at->diffForHumans() }}
                                </time>
                                <span>{{ number_format($regionalLead->views_count) }} lecturas</span>
                            </div>
                        </div>
                    </article>

                    <div class="latest-secondary" aria-label="Noticias secundarias">
                        @foreach ($regionalPosts->skip(1)->take(4) as $post)
                            <article class="secondary-story">
                                <a class="secondary-story__image" href="{{ route('posts.show', [$post->category, $post]) }}">
                                    <img src="{{ $post->coverUrl('card') }}" alt="{{ $post->media?->alt_text ?? '' }}" loading="lazy">
                                </a>
                                <div class="secondary-story__body">
                                    <a
                                        class="category-pill"
                                        style="--category-color: {{ $post->category->color }}"
                                        href="{{ route('posts.category', $post->category) }}"
                                    >{{ $post->category->name }}</a>
                                    <a class="location-link" href="{{ $post->location->publicUrl() }}">
                                        <span aria-hidden="true">⌖</span> {{ $post->location->name }}
                                    </a>
                                    <h3>
                                        <a href="{{ route('posts.show', [$post->category, $post]) }}">{{ $post->title }}</a>
                                    </h3>
                                    <p>{{ $post->excerpt }}</p>
                                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                                        {{ $post->published_at->diffForHumans() }}
                                    </time>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="ad-rail">
                        @foreach ($advertisements as $advertisement)
                            <x-ad-slot :advertisement="$advertisement" :position="$loop->iteration" />
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($mostViewedPosts->isNotEmpty())
                <section class="most-viewed" aria-labelledby="most-viewed-title">
                    <div class="most-viewed__heading">
                        <div>
                            <span class="eyebrow">Tendencias</span>
                            <h3 id="most-viewed-title">Las noticias más vistas</h3>
                        </div>
                        <div class="slider-controls" aria-label="Controles del slider">
                            <button
                                class="slider-autoplay-toggle"
                                type="button"
                                data-slider-autoplay-toggle
                                aria-label="Pausar movimiento automático"
                                aria-pressed="false"
                            >Ⅱ</button>
                            <button type="button" data-slider-prev aria-label="Noticias anteriores" disabled>←</button>
                            <button type="button" data-slider-next aria-label="Noticias siguientes">→</button>
                        </div>
                    </div>

                    <div
                        class="slider-shell"
                        data-news-slider
                        data-slider-mode="{{ $sliderSettings['mode'] }}"
                        data-slider-interval="{{ $sliderSettings['interval'] }}"
                        data-slider-loop="{{ $sliderSettings['loop'] ? 'true' : 'false' }}"
                    >
                        <div class="popular-track" data-slider-track tabindex="0">
                            @foreach ($mostViewedPosts as $post)
                                <article class="popular-card">
                                    <a class="popular-card__image" href="{{ route('posts.show', [$post->category, $post]) }}">
                                        <img src="{{ $post->coverUrl('card') }}" alt="{{ $post->media?->alt_text ?? '' }}" loading="lazy">
                                        <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    </a>
                                    <div class="popular-card__body">
                                        <a
                                            class="category-pill"
                                            style="--category-color: {{ $post->category->color }}"
                                            href="{{ route('posts.category', $post->category) }}"
                                        >{{ $post->category->name }}</a>
                                        <h4>
                                            <a href="{{ route('posts.show', [$post->category, $post]) }}">{{ $post->title }}</a>
                                        </h4>
                                        <p>{{ $post->excerpt }}</p>
                                        <div class="editorial-meta">
                                            <span>{{ number_format($post->views_count) }} lecturas</span>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </section>

    <section class="section section--soft" aria-labelledby="programs-title">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Nuestra señal</span>
                    <h2 id="programs-title">Programas</h2>
                </div>
                <a class="text-link" href="{{ route('programs.index') }}">Todos los programas →</a>
            </div>
            <div class="program-grid">
                @foreach ($programs as $program)
                    <x-program-card :program="$program" />
                @endforeach
            </div>
        </div>
    </section>

    <section class="cta-radio">
        <div class="container cta-radio__inner">
            <div>
                <span class="eyebrow">Siempre contigo</span>
                <h2>Noticias y radio en una sola plataforma</h2>
                <p>Escucha la señal en vivo y revisa lo mejor de nuestra programación desde cualquier dispositivo.</p>
            </div>
            <a class="button button--light button--large" href="{{ route('live') }}">Escuchar ahora</a>
        </div>
    </section>
@endsection
