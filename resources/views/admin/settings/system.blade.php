@extends('layouts.admin')

@section('title', 'Ajustes del sistema')
@section('eyebrow', 'Operación y seguridad')
@section('heading', 'Ajustes del sistema')

@section('content')
@php
    $tabs = [
        'regional' => ['Regionalización', 'Idioma, zona y formatos'],
        'smtp' => ['SMTP', 'Correo saliente'],
        'cache' => ['Caché', 'Rendimiento'],
        'maintenance' => ['Mantenimiento', 'Disponibilidad pública'],
        'backups' => ['Respaldos', 'Copias privadas'],
        'security' => ['Seguridad', 'Acceso administrativo'],
    ];
@endphp

<nav class="settings-tabs settings-tabs--system" aria-label="Ajustes del sistema">
    @foreach ($tabs as $key => [$label, $description])
        <a class="{{ $section === $key ? 'is-active' : '' }}" href="{{ route('admin.settings.system', $key) }}">
            <strong>{{ $label }}</strong><small>{{ $description }}</small>
        </a>
    @endforeach
</nav>

@if ($section === 'cache')
    <section class="panel settings-form" id="cache">
        <div class="panel__header"><div><span class="eyebrow">Rendimiento</span><h2>Caché de la aplicación</h2></div><span class="badge badge--success">Operativa</span></div>
        <div class="settings-callout"><strong>Limpieza segura</strong><p>Elimina vistas compiladas, rutas, configuración y caché de aplicación. Los contenidos y archivos no se modifican.</p></div>
        <form method="post" action="{{ route('admin.settings.cache.clear') }}">@csrf
            <button class="button button--primary settings-action-button" type="submit">Limpiar todas las cachés</button>
        </form>
    </section>
@elseif ($section === 'backups')
    <section class="panel settings-form" id="respaldos">
        <div class="panel__header"><div><span class="eyebrow">Continuidad</span><h2>Respaldos privados</h2></div>
            <form method="post" action="{{ route('admin.settings.backups.create') }}">@csrf<button class="button button--primary" type="submit">+ Crear respaldo</button></form>
        </div>
        <form method="post" action="{{ route('admin.settings.system.update', 'backups') }}" class="settings-grid">@csrf @method('PUT')
            <label>Conservar últimos <input type="number" name="retention" min="1" max="50" value="{{ old('retention', $backupSettings['retention']) }}"></label>
            <label class="check-row"><input type="checkbox" name="include_media" value="1" @checked(old('include_media', $backupSettings['include_media']))><span>Incluir biblioteca multimedia</span></label>
            <div><button class="button button--quiet" type="submit">Guardar política</button></div>
        </form>
        <div class="settings-backup-list">
            @forelse ($backupFiles as $file)
                <article><div><strong>{{ $file['name'] }}</strong><small>{{ number_format($file['size'] / 1048576, 2) }} MB · {{ $file['created_at'] }}</small></div>
                    <div><a class="button button--quiet" href="{{ route('admin.settings.backups.download', $file['name']) }}">Descargar</a>
                        <form method="post" action="{{ route('admin.settings.backups.delete', $file['name']) }}" data-confirm-delete="El respaldo se eliminará de forma permanente." data-delete-name="{{ $file['name'] }}">@csrf @method('DELETE')<button class="danger-link" type="submit">Eliminar</button></form>
                    </div>
                </article>
            @empty
                <div class="settings-empty">Todavía no existen respaldos.</div>
            @endforelse
        </div>
    </section>
@else
    <form method="post" action="{{ route('admin.settings.system.update', $section) }}" class="panel settings-form">
        @csrf @method('PUT')
        @if ($section === 'regional')
            <div class="panel__header"><div><span class="eyebrow">Localización</span><h2>Idioma, zona horaria y formatos</h2></div></div>
            <div class="settings-grid">
                <label id="idioma">Idioma <select name="locale"><option value="es" @selected($regional['locale']==='es')>Español</option><option value="en" @selected($regional['locale']==='en')>English</option></select></label>
                <label id="zona-horaria">Zona horaria <select name="timezone">@foreach (['America/Lima'=>'Lima (UTC-5)','America/Bogota'=>'Bogotá (UTC-5)','America/Mexico_City'=>'Ciudad de México','America/New_York'=>'Nueva York','Europe/Madrid'=>'Madrid'] as $value=>$label)<option value="{{ $value }}" @selected($regional['timezone']===$value)>{{ $label }}</option>@endforeach</select></label>
                <label id="formato-fecha">Formato de fecha <select name="date_format">@foreach (['d/m/Y'=>'30/07/2026','Y-m-d'=>'2026-07-30','d-m-Y'=>'30-07-2026'] as $value=>$label)<option value="{{ $value }}" @selected($regional['date_format']===$value)>{{ $label }}</option>@endforeach</select></label>
                <label>Formato de hora <select name="time_format"><option value="H:i" @selected($regional['time_format']==='H:i')>24 horas (18:45)</option><option value="h:i A" @selected($regional['time_format']==='h:i A')>12 horas (06:45 PM)</option></select></label>
            </div>
        @elseif ($section === 'smtp')
            <div class="panel__header"><div><span class="eyebrow">Correo</span><h2 id="smtp">Servidor SMTP</h2></div><span class="badge {{ $smtp['enabled'] ? 'badge--success' : 'badge--muted' }}">{{ $smtp['enabled'] ? 'Activo' : 'Inactivo' }}</span></div>
            <label class="check-row"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $smtp['enabled']))><span>Activar envío mediante SMTP</span></label>
            <div class="settings-grid">
                <label>Servidor <input name="host" value="{{ old('host', $smtp['host']) }}" placeholder="smtp.example.com"></label>
                <label>Puerto <input type="number" name="port" min="1" max="65535" value="{{ old('port', $smtp['port']) }}"></label>
                <label>Cifrado <select name="encryption"><option value="tls" @selected($smtp['encryption']==='tls')>TLS</option><option value="ssl" @selected($smtp['encryption']==='ssl')>SSL</option><option value="none" @selected($smtp['encryption']==='none')>Sin cifrado</option></select></label>
                <label>Usuario <input name="username" value="{{ old('username', $smtp['username']) }}" autocomplete="off"></label>
                <label>Contraseña <input type="password" name="password" autocomplete="new-password" placeholder="Sin cambios si queda vacía"><small>Se almacena cifrada.</small></label>
                <label>Correo remitente <input type="email" name="from_address" value="{{ old('from_address', $smtp['from_address']) }}"></label>
                <label>Nombre remitente <input name="from_name" value="{{ old('from_name', $smtp['from_name']) }}" required></label>
            </div>
        @elseif ($section === 'maintenance')
            <div class="panel__header"><div><span class="eyebrow">Disponibilidad</span><h2 id="mantenimiento">Modo mantenimiento</h2></div><span class="badge {{ $maintenance['enabled'] ? 'badge--warning' : 'badge--success' }}">{{ $maintenance['enabled'] ? 'Portal pausado' : 'Portal disponible' }}</span></div>
            <label class="check-row"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $maintenance['enabled']))><span>Mostrar página de mantenimiento al público</span></label>
            <div class="settings-grid">
                <label class="settings-grid__wide">Mensaje <textarea name="message" rows="4" maxlength="500" required>{{ old('message', $maintenance['message']) }}</textarea></label>
                <label>Retorno estimado <input type="datetime-local" name="return_at" value="{{ old('return_at', $maintenance['return_at']) }}"></label>
            </div>
            <div class="settings-callout"><strong>Acceso protegido</strong><p>El dashboard y el inicio de sesión permanecen disponibles para poder desactivar este modo.</p></div>
        @else
            <div class="panel__header"><div><span class="eyebrow">Protección</span><h2 id="seguridad">Seguridad administrativa</h2></div></div>
            <div class="settings-grid">
                <label class="check-row"><input type="checkbox" name="captcha_enabled" value="1" @checked(old('captcha_enabled', $security['captcha_enabled']))><span>CAPTCHA matemático en el login</span></label>
                <label>Intentos permitidos <input type="number" name="max_attempts" min="3" max="20" value="{{ old('max_attempts', $security['max_attempts']) }}"></label>
                <label>Bloqueo en minutos <input type="number" name="lock_minutes" min="1" max="1440" value="{{ old('lock_minutes', $security['lock_minutes']) }}"></label>
                <label>Duración de sesión <input type="number" name="session_lifetime" min="15" max="1440" value="{{ old('session_lifetime', $security['session_lifetime']) }}"><small>En minutos.</small></label>
                <label>Longitud mínima de contraseña <input type="number" name="password_min" min="8" max="64" value="{{ old('password_min', $security['password_min']) }}"></label>
                @foreach (['password_mixed_case'=>'Exigir mayúsculas y minúsculas','password_numbers'=>'Exigir números','password_symbols'=>'Exigir símbolos'] as $key=>$label)
                    <label class="check-row"><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $security[$key]))><span>{{ $label }}</span></label>
                @endforeach
            </div>
        @endif
        <footer class="settings-actions"><button class="button button--primary" type="submit">Guardar ajustes</button></footer>
    </form>

    @if ($section === 'smtp')
        <form method="post" action="{{ route('admin.settings.smtp.test') }}" class="panel settings-test-card">@csrf
            <div><span class="eyebrow">Diagnóstico</span><h3>Enviar correo de prueba</h3><p>Guarda primero la configuración y comprueba la entrega.</p></div>
            <label>Destinatario <input type="email" name="test_email" value="{{ auth()->user()->email }}" required></label>
            <button class="button button--quiet" type="submit">Enviar prueba</button>
        </form>
    @endif
@endif
@endsection
