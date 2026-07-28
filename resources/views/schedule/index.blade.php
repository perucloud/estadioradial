@extends('layouts.app')

@section('title', 'Programación | Estación Radial')

@php
    $days = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
@endphp

@section('content')
    <x-page-hero title="Programación semanal" eyebrow="Al aire">
        Consulta los programas y horarios de nuestra señal.
    </x-page-hero>

    <section class="section">
        <div class="container schedule-days">
            @foreach ($days as $dayNumber => $dayName)
                <section class="schedule-day">
                    <h2>{{ $dayName }}</h2>
                    <div class="schedule-list">
                        @forelse ($schedules->get($dayNumber, collect()) as $schedule)
                            <a href="{{ route('programs.show', $schedule->program) }}">
                                <span>{{ substr($schedule->starts_at, 0, 5) }}</span>
                                <strong>{{ $schedule->program->title }}</strong>
                                <small>{{ $schedule->program->hosts }}</small>
                            </a>
                        @empty
                            <div><span>Programación por confirmar</span></div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </section>
@endsection

