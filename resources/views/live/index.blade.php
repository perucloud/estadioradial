@extends('layouts.app')

@section('title', 'En vivo | Estación Radial')

@section('content')
    <section class="live-page">
        <div class="container live-page__grid">
            <div class="live-cover">
                <img src="{{ $audioStream?->coverUrl() ?? '/images/demo/stream-cover.svg' }}" alt="">
                <span><i></i> EN VIVO</span>
            </div>
            <div>
                <span class="eyebrow">Radio en línea</span>
                <h1>{{ $audioStream?->name ?? 'Señal principal' }}</h1>
                <p>Disfruta nuestra programación de noticias, cultura y entretenimiento desde cualquier dispositivo.</p>
                @if ($audioStream?->is_active && $audioStream?->playbackUrl())
                    <audio class="native-audio" controls preload="none" src="{{ $audioStream->playbackUrl() }}"></audio>
                @else
                    <div class="stream-notice">
                        <strong>Transmisión pendiente de configuración</strong>
                        <p>{{ $audioStream?->fallback_message ?: 'La plataforma está preparada. El administrador deberá ingresar la URL HTTPS del proveedor de streaming.' }}</p>
                    </div>
                @endif
                <a class="text-link" href="{{ route('schedule') }}">Consultar programación →</a>
            </div>
        </div>
    </section>

    <section id="video" class="section section--soft">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Transmisión audiovisual</span>
                    <h2>Video en vivo</h2>
                </div>
            </div>
            @if ($videoStream?->is_active && $videoStream?->playbackUrl())
                <div class="video-frame">
                    @if ($videoStream->format === 'hls')
                        <video controls preload="none" src="{{ $videoStream->playbackUrl() }}"></video>
                    @else
                        <iframe src="{{ $videoStream->playbackUrl() }}" title="{{ $videoStream->name }}" allowfullscreen></iframe>
                    @endif
                </div>
            @else
                <div class="video-placeholder">
                    <span aria-hidden="true">▶</span>
                    <div>
                        <h3>Video opcional</h3>
                        <p>{{ $videoStream?->fallback_message ?: 'Esta sección aparecerá cuando el usuario active una transmisión de YouTube o HLS.' }}</p>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
