@extends('layouts.app')

@section('title', 'Estación Radial | Noticias y radio en vivo')

@section('content')
    <section class="hero">
        <div class="container">
            @if ($featuredPosts->isNotEmpty())
                <a class="hero-banner" href="{{ route('programs.index') }}" aria-label="Conoce nuestra programación">
                    <img src="/images/demo/banner-radio.svg" alt="Estación Radial, voces que conectan">
                </a>

                <div class="hero-grid">
                    @php($leadPost = $featuredPosts->first())

                    <article class="lead-story">
                        <a class="lead-story__image" href="{{ route('posts.show', [$leadPost->category, $leadPost]) }}">
                            <img src="{{ $leadPost->image }}" alt="">
                            <span
                                class="category-pill"
                                style="--category-color: {{ $leadPost->category->color }}"
                            >{{ $leadPost->category->name }}</span>
                        </a>
                        <div class="lead-story__body">
                            <h1>
                                <a href="{{ route('posts.show', [$leadPost->category, $leadPost]) }}">
                                    {{ $leadPost->title }}
                                </a>
                            </h1>
                            <p>{{ $leadPost->excerpt }}</p>
                            <time datetime="{{ $leadPost->published_at->toIso8601String() }}">
                                {{ $leadPost->published_at->format('H:i') }} HS.
                            </time>
                        </div>
                    </article>

                    <div class="hero-grid__side">
                        @foreach ($featuredPosts->skip(1)->take(3) as $post)
                            <article class="hero-story {{ $loop->even ? 'hero-story--reversed' : '' }}">
                                <a class="hero-story__image" href="{{ route('posts.show', [$post->category, $post]) }}">
                                    <img src="{{ $post->image }}" alt="">
                                </a>
                                <div class="hero-story__body">
                                    <a
                                        class="category-pill"
                                        style="--category-color: {{ $post->category->color }}"
                                        href="{{ route('posts.category', $post->category) }}"
                                    >{{ $post->category->name }}</a>
                                    <h2>
                                        <a href="{{ route('posts.show', [$post->category, $post]) }}">
                                            {{ $post->title }}
                                        </a>
                                    </h2>
                                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                                        {{ $post->published_at->format('H:i') }} HS.
                                    </time>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
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

    <section class="section" aria-labelledby="latest-title">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Información al momento</span>
                    <h2 id="latest-title">Últimas noticias</h2>
                </div>
                <a class="text-link" href="{{ route('posts.index') }}">Ver todas las noticias →</a>
            </div>
            <div class="news-grid">
                @foreach ($latestPosts as $post)
                    <x-news-card :post="$post" />
                @endforeach
            </div>
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
