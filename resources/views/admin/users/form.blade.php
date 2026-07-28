@extends('layouts.admin')

@php($editing = isset($user))
@section('title', $editing ? 'Editar usuario' : 'Nuevo usuario')
@section('eyebrow', 'Seguridad y acceso')
@section('heading', $editing ? 'Editar usuario' : 'Crear usuario')

@section('content')
    <form
        method="post"
        action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}"
        class="panel panel--form form-stack"
    >
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="form-grid">
            <label>
                Nombre
                <input type="text" name="name" value="{{ old('name', $user?->name) }}" maxlength="120" required autofocus>
                @error('name') <small class="field-error">{{ $message }}</small> @enderror
            </label>
            <label>
                Correo electrónico
                <input type="email" name="email" value="{{ old('email', $user?->email) }}" required>
                @error('email') <small class="field-error">{{ $message }}</small> @enderror
            </label>
        </div>

        <label>
            Rol
            <select name="role" required>
                @foreach ($roles as $role)
                    <option
                        value="{{ $role->slug }}"
                        @selected(old('role', $user?->roles->first()?->slug) === $role->slug)
                    >{{ $role->name }}</option>
                @endforeach
            </select>
            @error('role') <small class="field-error">{{ $message }}</small> @enderror
        </label>

        <div class="form-grid">
            <label>
                {{ $editing ? 'Nueva contraseña (opcional)' : 'Contraseña temporal' }}
                <input type="password" name="password" autocomplete="new-password" @required(! $editing)>
                @error('password') <small class="field-error">{{ $message }}</small> @enderror
            </label>
            <label>
                Confirmar contraseña
                <input type="password" name="password_confirmation" autocomplete="new-password" @required(! $editing)>
            </label>
        </div>

        @if ($editing)
            <label class="check-row">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))>
                <span>Cuenta activa</span>
            </label>
        @endif

        @if (auth()->user()->hasRole('superadmin'))
            <fieldset class="permissions-box">
                <legend>Permisos directos para administradores</legend>
                <p>Solo se aplican cuando el rol seleccionado es Administrador.</p>
                @foreach ($permissions as $module => $modulePermissions)
                    <div class="permission-group">
                        <strong>{{ ucfirst($module) }}</strong>
                        @foreach ($modulePermissions as $permission)
                            <label class="check-row">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->id }}"
                                    @checked(in_array($permission->id, old('permissions', $user?->permissions->pluck('id')->all() ?? [])))
                                >
                                <span>{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </fieldset>
        @endif

        <div class="form-actions">
            <a class="button button--quiet" href="{{ route('admin.users.index') }}">Cancelar</a>
            <button class="button button--primary" type="submit">{{ $editing ? 'Guardar cambios' : 'Crear usuario' }}</button>
        </div>
    </form>
@endsection
