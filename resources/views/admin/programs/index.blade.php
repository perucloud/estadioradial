@extends('layouts.admin')

@section('title', 'Programas')
@section('eyebrow', 'Radio')
@section('heading', 'Programas')

@section('content')
    <div class="page-actions">
        <p>Administra la oferta radial, sus conductores, imágenes y visibilidad pública.</p>
        <a class="button button--primary" href="{{ route('admin.programs.create') }}">Nuevo programa</a>
    </div>

    <section class="panel table-panel">
        <div class="responsive-table">
            <table>
                <thead><tr><th>Programa</th><th>Conductores</th><th>Horarios</th><th>Orden</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($programs as $program)
                        <tr>
                            <td><strong>{{ $program->title }}</strong><small>{{ $program->summary }}</small></td>
                            <td>{{ $program->presenters->pluck('name')->join(', ') ?: ($program->hosts ?: 'Sin asignar') }}</td>
                            <td>{{ $program->schedules_count }}</td>
                            <td>{{ $program->display_order }}</td>
                            <td><span class="badge {{ $program->is_active ? 'badge--success' : 'badge--muted' }}">{{ $program->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                            <td><a class="button button--quiet button--compact" href="{{ route('admin.programs.edit', $program) }}">Editar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Todavía no hay programas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $programs->links() }}
    </section>
@endsection
