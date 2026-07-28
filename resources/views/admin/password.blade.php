@extends('layouts.admin')

@section('title', 'Seguridad')
@section('eyebrow', 'Mi cuenta')
@section('heading', 'Cambiar contraseña')

@section('content')
    <section class="panel panel--form">
        <p class="form-intro">La nueva contraseña debe contener mayúsculas, minúsculas y números.</p>

        <form method="post" action="{{ route('admin.password.update') }}" class="form-stack">
            @csrf
            @method('PUT')
            <label>
                Contraseña actual
                <input type="password" name="current_password" autocomplete="current-password" required autofocus>
                @error('current_password') <small class="field-error">{{ $message }}</small> @enderror
            </label>
            <label>
                Nueva contraseña
                <input type="password" name="password" autocomplete="new-password" required>
                @error('password') <small class="field-error">{{ $message }}</small> @enderror
            </label>
            <label>
                Confirmar nueva contraseña
                <input type="password" name="password_confirmation" autocomplete="new-password" required>
            </label>
            <button class="button button--primary" type="submit">Actualizar contraseña</button>
        </form>
    </section>
@endsection
