@extends('layouts.auth')

@section('title', 'Crear contraseña')

@section('content')
    <span class="eyebrow">Recuperación segura</span>
    <h1>Crear contraseña</h1>
    <p class="form-intro">Usa al menos ocho caracteres, con letras y números.</p>

    <form method="post" action="{{ route('password.update') }}" class="form-stack">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>
            Correo electrónico
            <input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="username" required>
            @error('email') <small class="field-error">{{ $message }}</small> @enderror
        </label>
        <label>
            Nueva contraseña
            <input type="password" name="password" autocomplete="new-password" required>
            @error('password') <small class="field-error">{{ $message }}</small> @enderror
        </label>
        <label>
            Confirmar contraseña
            <input type="password" name="password_confirmation" autocomplete="new-password" required>
        </label>
        <button class="button button--primary button--wide" type="submit">Guardar contraseña</button>
    </form>
@endsection
