@extends('layouts.auth')

@section('title', 'Recuperar contraseña')

@section('content')
    <span class="eyebrow">Recuperación segura</span>
    <h1>Recuperar contraseña</h1>
    <p class="form-intro">Te enviaremos un enlace temporal si el correo pertenece a una cuenta.</p>

    <form method="post" action="{{ route('password.email') }}" class="form-stack">
        @csrf
        <label>
            Correo electrónico
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
            @error('email') <small class="field-error">{{ $message }}</small> @enderror
        </label>
        <button class="button button--primary button--wide" type="submit">Enviar enlace</button>
    </form>

    <a class="form-link" href="{{ route('login') }}">Volver al inicio de sesión</a>
@endsection
