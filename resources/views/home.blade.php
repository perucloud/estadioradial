@extends('layouts.app')

@section('title', 'Estación Radial | Noticias y radio en vivo')

@section('content')
    <section class="hero section">
        <div class="container">
            @if ($featuredPosts->isNotEmpty())
                <div class="hero-grid">
                    <x-news-card :post="$featuredPosts->first()" featured class="hero-grid__main" />
                    <div class="hero-grid__side">
                        @foreach ($featuredPosts->skip(1) as $post)
                            <x-news-card :post="$post" />
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

