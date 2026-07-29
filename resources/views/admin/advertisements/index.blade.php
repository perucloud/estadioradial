@extends('layouts.admin')
@section('title', 'Publicidad')
@section('eyebrow', 'Comercial')
@section('heading', 'Publicidad y banners')
@section('content')
<details class="panel taxonomy-create" @if($errors->any()) open @endif>
    <summary class="taxonomy-create__summary"><span><span class="eyebrow">Nueva campaña</span><strong>Añadir publicidad</strong></span><span class="button button--primary">+ Publicidad</span></summary>
    @include('admin.advertisements.partials.form', ['advertisement' => null, 'action' => route('admin.advertisements.store')])
</details>
<div class="stream-admin-grid">
@foreach($advertisements as $advertisement)
    <section class="panel">
        <div class="panel__header"><div><span class="eyebrow">{{ $placements[$advertisement->placement] }}</span><h2>{{ $advertisement->name }}</h2></div><span class="badge {{ $advertisement->is_active ? 'badge--success' : 'badge--muted' }}">{{ $advertisement->is_active ? 'Activa' : 'Inactiva' }}</span></div>
        @include('admin.advertisements.partials.form', ['advertisement' => $advertisement, 'action' => route('admin.advertisements.update', $advertisement)])
        <form method="post" action="{{ route('admin.advertisements.destroy', $advertisement) }}">@csrf @method('DELETE')<button class="danger-link">Eliminar</button></form>
    </section>
@endforeach
</div>
@endsection
