<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = in_array($request->query('role'), ['superadmin', 'admin', 'editor', 'locutor'], true)
            ? (string) $request->query('role')
            : '';

        return view('admin.users.index', [
            'users' => User::query()
                ->with('roles')
                ->when($role !== '', fn ($query) => $query->whereHas('roles', fn ($query) => $query->where('slug', $role)))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'roleFilter' => $role,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.form', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);
        $role = Role::query()->where('slug', $data['role'])->firstOrFail();
        $this->authorizeRole($request->user(), $role);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $user->roles()->sync([$role->id]);
        $this->syncPermissions($request, $user, $role);
        $this->log($request, 'user.created', $user, ['role' => $role->slug]);

        return redirect()->route('admin.users.index')
            ->with('status', 'Usuario creado. Deberá cambiar su contraseña al ingresar.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorizeTarget($request->user(), $user);

        return view('admin.users.form', $this->formData($request, $user));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeTarget($request->user(), $user);
        $data = $this->validateUser($request, $user);
        $role = Role::query()->where('slug', $data['role'])->firstOrFail();
        $this->authorizeRole($request->user(), $role);

        if ($request->user()->is($user)) {
            abort_if(! $request->boolean('is_active') || ! $user->roles->contains('id', $role->id), 422);
        }

        $changes = [
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($data['password'])) {
            $changes['password'] = Hash::make($data['password']);
            $changes['must_change_password'] = true;
            $changes['password_changed_at'] = null;
        }

        $user->update($changes);
        $user->roles()->sync([$role->id]);
        $this->syncPermissions($request, $user, $role);
        $this->log($request, 'user.updated', $user, ['role' => $role->slug]);

        return redirect()->route('admin.users.index')->with('status', 'Usuario actualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, ?User $user = null): array
    {
        $roles = $request->user()->hasRole('superadmin')
            ? Role::query()->orderByDesc('level')->get()
            : Role::query()->whereIn('slug', ['editor', 'locutor'])->orderByDesc('level')->get();

        $permissions = Permission::query()
            ->whereNotIn('slug', ['users.create.admin', 'users.create.superadmin'])
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');

        return compact('user', 'roles', 'permissions');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', 'string', Rule::exists('roles', 'slug')],
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);
    }

    private function authorizeRole(User $actor, Role $role): void
    {
        if ($actor->hasRole('superadmin')) {
            return;
        }

        abort_unless(
            $actor->hasPermission('users.create.editorial')
                && in_array($role->slug, ['editor', 'locutor'], true),
            403,
        );
    }

    private function authorizeTarget(User $actor, User $target): void
    {
        if ($actor->hasRole('superadmin')) {
            return;
        }

        abort_unless(
            $actor->hasPermission('users.update')
                && $target->highestRoleLevel() < $actor->highestRoleLevel(),
            403,
        );
    }

    private function syncPermissions(Request $request, User $user, Role $role): void
    {
        if (! $request->user()->hasRole('superadmin') || $role->slug !== 'admin') {
            $user->permissions()->detach();

            return;
        }

        $allowedIds = Permission::query()
            ->whereNotIn('slug', ['users.create.admin', 'users.create.superadmin'])
            ->whereIn('id', $request->input('permissions', []))
            ->pluck('id');

        $user->permissions()->sync($allowedIds);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(Request $request, string $action, User $subject, array $properties): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'properties' => $properties,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);
    }
}
