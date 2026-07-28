@extends('layouts.admin')

@section('title', 'Usuarios')
@section('eyebrow', 'Seguridad y acceso')
@section('heading', 'Usuarios')

@section('content')
    <div class="page-actions">
        <p>Administra las cuentas sin exceder tu nivel de autorización.</p>
        @if (auth()->user()->hasPermission('users.create.editorial'))
            <a class="button button--primary" href="{{ route('admin.users.create') }}">Nuevo usuario</a>
        @endif
    </div>

    <section class="panel table-panel">
        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último acceso</th>
                        <th><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                <small>{{ $user->email }}</small>
                            </td>
                            <td>{{ $user->roles->pluck('name')->join(', ') ?: 'Sin rol' }}</td>
                            <td>
                                <span class="badge {{ $user->is_active ? 'badge--success' : 'badge--muted' }}">
                                    {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>{{ $user->last_login_at?->diffForHumans() ?? 'Nunca' }}</td>
                            <td>
                                @if (auth()->user()->hasPermission('users.update') && (auth()->user()->hasRole('superadmin') || $user->highestRoleLevel() < auth()->user()->highestRoleLevel()))
                                    <a class="table-link" href="{{ route('admin.users.edit', $user) }}">Editar</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </section>
@endsection
