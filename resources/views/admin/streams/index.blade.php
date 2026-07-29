@extends('layouts.admin')

@section('title', 'Streaming')
@section('eyebrow', 'Señales')
@section('heading', 'Audio y video streaming')

@section('content')
    <div class="alert alert--info">El portal reproduce la señal directamente desde el proveedor. Solo se aceptan direcciones HTTPS y nunca código JavaScript de terceros.</div>

    <details class="panel taxonomy-create" @if ($errors->any()) open @endif>
        <summary class="taxonomy-create__summary"><span><span class="eyebrow">Nueva señal</span><strong>Configurar proveedor</strong></span><span class="button button--primary">+ Añadir señal</span></summary>
        @include('admin.streams.partials.form', ['stream' => null, 'action' => route('admin.streams.store')])
    </details>

    <div class="stream-admin-grid">
        @foreach ($streams as $stream)
            <section class="panel">
                <div class="panel__header">
                    <div><span class="eyebrow">{{ $stream->type === 'audio' ? 'Audio' : 'Video' }}</span><h2>{{ $stream->name }}</h2></div>
                    <span class="badge {{ $stream->is_active ? 'badge--success' : 'badge--muted' }}">{{ $stream->is_active ? 'Activa' : 'Inactiva' }}</span>
                </div>
                @include('admin.streams.partials.form', ['stream' => $stream, 'action' => route('admin.streams.update', $stream)])
                <form method="post" action="{{ route('admin.streams.destroy', $stream) }}" onsubmit="return confirm('¿Enviar esta señal a la papelera?')">@csrf @method('DELETE')<button class="danger-link">Eliminar señal</button></form>
            </section>
        @endforeach
    </div>
@endsection
