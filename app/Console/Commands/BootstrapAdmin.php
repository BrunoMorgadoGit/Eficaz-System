<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BootstrapAdmin extends Command
{
    private const BOOTSTRAP_LOCK_KEY = 'bootstrap-admin:initial-account';

    private const BOOTSTRAP_LOCK_SECONDS = 120;

    protected $signature = 'app:bootstrap-admin';

    protected $description = 'Create the first administrator with an interactive password prompt';

    public function handle(): int
    {
        if ($this->hasActiveAdministrator()) {
            $this->error('Já existe um administrador ativo. Use a gestão de usuários do portal.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Nome completo'));
        $email = strtolower(trim((string) $this->ask('E-mail')));
        $identity = Validator::make(compact('name', 'email'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($identity->fails()) {
            $this->error($identity->errors()->first());

            return self::FAILURE;
        }

        $password = (string) $this->secret('Senha (mínimo de 8 caracteres)');
        $passwordConfirmation = (string) $this->secret('Confirme a senha');
        $passwords = Validator::make([
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
        ]);

        if ($passwords->fails()) {
            $this->error($passwords->errors()->first());

            return self::FAILURE;
        }

        $lock = Cache::store('database')->lock(self::BOOTSTRAP_LOCK_KEY, self::BOOTSTRAP_LOCK_SECONDS);

        if (! $lock->get()) {
            $this->error('Outro processo está criando o administrador inicial. Tente novamente.');

            return self::FAILURE;
        }

        try {
            if ($this->hasActiveAdministrator()) {
                $this->error('Já existe um administrador ativo. Use a gestão de usuários do portal.');

                return self::FAILURE;
            }

            User::query()->create([
                'name' => $name,
                'email' => $email,
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make($password),
                'is_active' => true,
            ]);

            $this->info('Administrador inicial cadastrado.');

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function hasActiveAdministrator(): bool
    {
        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->exists();
    }
}
