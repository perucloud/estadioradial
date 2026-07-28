<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;

class AdminAccess
{
    public const ROLES = [
        'superadmin' => ['name' => 'Superadministrador', 'level' => 100],
        'admin' => ['name' => 'Administrador', 'level' => 70],
        'editor' => ['name' => 'Editor', 'level' => 40],
        'locutor' => ['name' => 'Locutor', 'level' => 20],
    ];

    public const PERMISSIONS = [
        'dashboard.view' => ['Resumen', 'dashboard'],
        'users.view' => ['Ver usuarios', 'users'],
        'users.create.editorial' => ['Crear editores y locutores', 'users'],
        'users.create.admin' => ['Crear administradores', 'users'],
        'users.create.superadmin' => ['Crear superadministradores', 'users'],
        'users.update' => ['Editar usuarios', 'users'],
        'users.assign_permissions' => ['Asignar permisos', 'users'],
        'news.view' => ['Ver noticias', 'news'],
        'news.create' => ['Crear noticias', 'news'],
        'news.update' => ['Editar noticias', 'news'],
        'news.publish' => ['Publicar noticias', 'news'],
        'media.manage' => ['Administrar multimedia', 'media'],
        'categories.manage' => ['Administrar categorías', 'categories'],
        'programs.manage' => ['Administrar programas', 'programs'],
        'schedule.manage' => ['Administrar programación', 'schedule'],
        'stream.manage' => ['Administrar streaming', 'stream'],
        'appearance.manage' => ['Administrar apariencia', 'appearance'],
        'advertising.manage' => ['Administrar publicidad', 'advertising'],
        'settings.manage' => ['Administrar configuración', 'settings'],
        'analytics.view' => ['Ver estadísticas', 'analytics'],
        'activity.view' => ['Ver actividad', 'activity'],
    ];

    public static function sync(): Collection
    {
        $roles = collect(self::ROLES)->mapWithKeys(function (array $definition, string $slug) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $definition['name'], 'level' => $definition['level']],
            );

            return [$slug => $role];
        });

        $permissions = collect(self::PERMISSIONS)->mapWithKeys(function (array $definition, string $slug) {
            $permission = Permission::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $definition[0], 'module' => $definition[1]],
            );

            return [$slug => $permission];
        });

        $roles['superadmin']->permissions()->sync($permissions->pluck('id'));
        $roles['admin']->permissions()->sync($permissions
            ->only(['dashboard.view', 'users.view', 'users.create.editorial', 'users.update', 'analytics.view'])
            ->pluck('id'));
        $roles['editor']->permissions()->sync($permissions
            ->only(['dashboard.view', 'news.view', 'news.create', 'news.update', 'media.manage', 'categories.manage', 'analytics.view'])
            ->pluck('id'));
        $roles['locutor']->permissions()->sync($permissions
            ->only(['dashboard.view', 'programs.manage', 'schedule.manage', 'analytics.view'])
            ->pluck('id'));

        return $roles;
    }
}
