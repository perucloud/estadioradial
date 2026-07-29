@extends('layouts.app')

@section('title', $program->title.' | Estación Radial')
@section('description', $program->summary)

@php
    $days = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
@endphp

@section('content')
    <section class="program-detail">
        <div class="container program-detail__grid">
            <img src="{{ $program->imageUrl() }}" alt="{{ $program->title }}">
            <div>
                <span class="eyebrow">Programa</span>
                <h1>{{ $program->title }}</h1>
                <p class="program-detail__lead">{{ $program->summary }}</p>
                @if ($program->hosts)
                    <p><strong>Conduce:</strong> {{ $program->hosts }}</p>
                @endif
                @if ($program->presenters->isNotEmpty())
                    <p><strong>Locutores:</strong> {{ $program->presenters->pluck('name')->join(', ') }}</p>
                @endif
                <p>{{ $program->description }}</p>
                <a class="button button--primary" href="{{ route('live') }}">Escuchar radio</a>
            </div>
        </div>
    </section>

    <section class="section section--soft">
        <div class="container container--narrow">
            <div class="section-heading"><h2>Horarios</h2></div>
            <div class="schedule-list">
                @foreach ($program->schedules as $schedule)
                    <div>
                        <strong>{{ $days[$schedule->day_of_week] }}</strong>
                        <span>{{ substr($schedule->starts_at, 0, 5) }} – {{ substr($schedule->ends_at, 0, 5) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
