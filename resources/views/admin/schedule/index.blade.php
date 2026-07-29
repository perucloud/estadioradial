@extends('layouts.admin')

@section('title', 'Programación radial')
@section('eyebrow', 'Parrilla semanal')
@section('heading', 'Programación radial')

@section('content')
    <details class="panel taxonomy-create" @if ($errors->any()) open @endif>
        <summary class="taxonomy-create__summary"><span><span class="eyebrow">Nuevo espacio</span><strong>Añadir horario</strong></span><span class="button button--primary">+ Programar</span></summary>
        <form method="post" action="{{ route('admin.schedule.store') }}" class="form-stack taxonomy-form">
            @csrf
            <div class="form-grid form-grid--three">
                <label>Programa <select name="program_id" required>@foreach($programs as $program)<option value="{{ $program->id }}">{{ $program->title }}</option>@endforeach</select></label>
                <label>Día <select name="day_of_week">@foreach($days as $number => $name)<option value="{{ $number }}">{{ $name }}</option>@endforeach</select></label>
                <label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked><span>Horario activo</span></label>
            </div>
            <div class="form-grid">
                <label>Inicio <input type="time" name="starts_at" value="{{ old('starts_at', '08:00') }}" required></label>
                <label>Fin <input type="time" name="ends_at" value="{{ old('ends_at', '09:00') }}" required></label>
            </div>
            <fieldset><legend>Copiar también a</legend><div class="admin-choice-grid">@foreach($days as $number => $name)<label class="check-row"><input type="checkbox" name="copy_to_days[]" value="{{ $number }}"><span>{{ $name }}</span></label>@endforeach</div></fieldset>
            <button class="button button--primary" type="submit">Guardar horario</button>
        </form>
    </details>

    <div class="schedule-admin-grid">
        @foreach ($days as $dayNumber => $dayName)
            <section class="panel">
                <div class="panel__header"><div><span class="eyebrow">Día {{ $dayNumber }}</span><h2>{{ $dayName }}</h2></div></div>
                <div class="schedule-admin-list">
                    @forelse ($schedules->get($dayNumber, collect()) as $schedule)
                        <details>
                            <summary><strong>{{ substr($schedule->starts_at, 0, 5) }}–{{ substr($schedule->ends_at, 0, 5) }}</strong><span>{{ $schedule->program->title }}</span><span class="badge {{ $schedule->is_active ? 'badge--success' : 'badge--muted' }}">{{ $schedule->is_active ? 'Activo' : 'Inactivo' }}</span></summary>
                            <form method="post" action="{{ route('admin.schedule.update', $schedule) }}" class="form-stack form-stack--compact">
                                @csrf @method('PUT')
                                <input type="hidden" name="day_of_week" value="{{ $dayNumber }}">
                                <label>Programa <select name="program_id">@foreach($programs as $program)<option value="{{ $program->id }}" @selected($schedule->program_id === $program->id)>{{ $program->title }}</option>@endforeach</select></label>
                                <div class="form-grid"><label>Inicio <input type="time" name="starts_at" value="{{ substr($schedule->starts_at, 0, 5) }}"></label><label>Fin <input type="time" name="ends_at" value="{{ substr($schedule->ends_at, 0, 5) }}"></label></div>
                                <label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($schedule->is_active)><span>Activo</span></label>
                                <button class="button button--primary button--compact">Guardar</button>
                            </form>
                            <form method="post" action="{{ route('admin.schedule.destroy', $schedule) }}" onsubmit="return confirm('¿Eliminar este horario?')">@csrf @method('DELETE')<button class="danger-link">Eliminar horario</button></form>
                        </details>
                    @empty
                        <p class="field-help">Sin programación.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
@endsection
