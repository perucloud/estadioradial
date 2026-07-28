@extends('layouts.auth')

@section('title', 'Iniciar sesión')

@section('content')
    <span class="eyebrow">Acceso administrativo</span>
    <h1>Bienvenido</h1>
    <p class="form-intro">Ingresa tus credenciales para administrar el portal.</p>

    <form method="post" action="{{ route('login.store') }}" class="form-stack">
        @csrf

        <label>
            Correo electrónico
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" autofocus required>
            @error('email') <small class="field-error">{{ $message }}</small> @enderror
        </label>

        <label>
            Contraseña
            <input type="password" name="password" autocomplete="current-password" required>
            @error('password') <small class="field-error">{{ $message }}</small> @enderror
        </label>

        @if ($captchaEnabled)
            <label>
                Verificación: ¿cuánto es {{ $captcha['question'] }}?
                <input type="number" name="captcha" inputmode="numeric" autocomplete="off" required>
                @error('captcha') <small class="field-error">{{ $message }}</small> @enderror
            </label>
        @endif

        <label class="check-row">
            <input type="checkbox" name="remember" value="1">
            <span>Mantener la sesión en este equipo</span>
        </label>

        <button class="button button--primary button--wide" type="submit">Ingresar al dashboard</button>
    </form>

    <a class="form-link" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
@endsection
