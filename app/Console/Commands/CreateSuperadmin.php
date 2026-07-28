<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSuperadmin extends Command
{
    protected $signature = 'admin:create-superadmin
        {email? : Correo de la cuenta maestra}
        {--name= : Nombre visible}
        {--password-stdin : Leer la contraseña desde la entrada estándar}';

    protected $description = 'Crea o actualiza de forma segura la cuenta superadministradora';

    public function handle(): int
    {
        $email = mb_strtolower((string) ($this->argument('email') ?: $this->ask('Correo')));
        $name = (string) ($this->option('name') ?: $this->ask('Nombre', 'Superadministrador'));

        if ($this->option('password-stdin')) {
            $password = trim((string) stream_get_contents(STDIN));
            $confirmation = $password;
        } else {
            $password = (string) $this->secret('Contraseña temporal');
            $confirmation = (string) $this->secret('Confirmar contraseña');
        }

        $validator = Validator::make(compact('email', 'password', 'confirmation'), [
            'email' => ['required', 'email'],
            'password' => ['required', 'same:confirmation', Password::min(8)->letters()->numbers()],
        ], [
            'password.same' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $roles = AdminAccess::sync();
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_active' => true,
                'must_change_password' => true,
                'password_changed_at' => null,
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ],
        );

        $user->roles()->sync([$roles['superadmin']->id]);
        $user->permissions()->detach();

        $this->info("Cuenta superadministradora preparada para {$email}.");
        $this->warn('La contraseña deberá cambiarse en el primer acceso.');

        return self::SUCCESS;
    }
}
