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
@endsection
