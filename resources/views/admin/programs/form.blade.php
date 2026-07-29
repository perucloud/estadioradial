@extends('layouts.admin')

@php
    $editing = $program !== null;
    $selectedPresenters = old('presenter_ids', $program?->presenters->pluck('id')->all() ?? []);
@endphp

@section('title', $editing ? 'Editar programa' : 'Nuevo programa')
@section('eyebrow', 'Radio')
@section('heading', $editing ? 'Editar programa' : 'Nuevo programa')

@section('content')
    <form method="post" action="{{ $editing ? route('admin.programs.update', $program) : route('admin.programs.store') }}" class="form-stack">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="panel form-stack">
            <div class="form-grid">
                <label>Nombre <input name="title" value="{{ old('title', $program?->title) }}" maxlength="180" required></label>
                <label>Slug <input name="slug" value="{{ old('slug', $program?->slug) }}" maxlength="200" placeholder="Se genera desde el nombre"></label>
            </div>
            <label>Resumen <textarea name="summary" rows="3" maxlength="500" required>{{ old('summary', $program?->summary) }}</textarea></label>
            <label>Descripción <textarea name="description" rows="7" maxlength="10000" required>{{ old('description', $program?->description) }}</textarea></label>
            <label>Crédito de conducción <input name="hosts" value="{{ old('hosts', $program?->hosts) }}" maxlength="500" placeholder="Texto público opcional"></label>
            <div class="form-grid">
                <label>Orden <input type="number" name="display_order" value="{{ old('display_order', $program?->display_order ?? 100) }}" min="0" max="65535"></label>
                <label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $program?->is_active ?? true))><span>Programa activo y visible</span></label>
            </div>
        </section>

        <section class="panel form-stack">
            <div class="panel__header"><div><span class="eyebrow">Equipo</span><h2>Locutores asignados</h2></div></div>
            <div class="admin-choice-grid">
                @forelse ($presenters as $presenter)
                    <label class="check-row"><input type="checkbox" name="presenter_ids[]" value="{{ $presenter->id }}" @checked(in_array($presenter->id, $selectedPresenters))><span>{{ $presenter->name }}</span></label>
                @empty
                    <p class="field-help">Crea usuarios con rol Locutor para asignarlos aquí. También puedes utilizar el crédito de conducción.</p>
                @endforelse
            </div>
        </section>

        <section class="panel form-stack">
            <div class="panel__header"><div><span class="eyebrow">Media</span><h2>Imagen del programa</h2></div></div>
            <div class="admin-media-options">
                <label class="admin-media-option"><input type="radio" name="media_id" value="" @checked(! old('media_id', $program?->media_id))><span>Sin imagen de biblioteca</span></label>
                @foreach ($mediaItems as $media)
                    <label class="admin-media-option">
                        <input type="radio" name="media_id" value="{{ $media->id }}" @checked((int) old('media_id', $program?->media_id) === $media->id)>
                        <img src="{{ $media->url('thumb') }}" alt="{{ $media->alt_text }}">
                        <span>{{ $media->original_name }}</span>
                    </label>
                @endforeach
            </div>
        </section>

        <div class="form-actions">
            <a class="button button--quiet" href="{{ route('admin.programs.index') }}">Cancelar</a>
            <button class="button button--primary" type="submit">{{ $editing ? 'Guardar cambios' : 'Crear programa' }}</button>
        </div>
    </form>

    @if ($editing)
        <form method="post" action="{{ route('admin.programs.destroy', $program) }}" onsubmit="return confirm('¿Enviar este programa a la papelera?')">
            @csrf @method('DELETE')
            <button class="danger-link" type="submit" @disabled($program->schedules()->exists())>Enviar a la papelera</button>
        </form>
    @endif
@endsection
