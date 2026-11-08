<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_skips_non_local_environments(): void
    {
        config(['app.env' => 'production']);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_initial_admin_can_be_created_without_a_fixed_password(): void
    {
        $this->artisan('app:bootstrap-admin')
            ->expectsQuestion('Nome completo', 'Admin Inicial')
            ->expectsQuestion('E-mail', 'admin@example.test')
            ->expectsQuestion('Senha (mínimo de 8 caracteres)', 'SenhaSegura123!')
            ->expectsQuestion('Confirme a senha', 'SenhaSegura123!')
            ->expectsOutput('Administrador inicial cadastrado.')
            ->assertExitCode(0);

        $admin = User::query()->where('email', 'admin@example.test')->sole();

        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('SenhaSegura123!', $admin->password));
    }

    public function test_initial_admin_command_refuses_to_run_after_bootstrap(): void
    {
        User::factory()->admin()->create();

        $this->artisan('app:bootstrap-admin')
            ->expectsOutput('Já existe um administrador ativo. Use a gestão de usuários do portal.')
            ->assertExitCode(1);

        $this->assertSame(1, User::query()->where('role', User::ROLE_ADMIN)->count());
    }

    public function test_initial_admin_command_does_not_create_an_account_while_another_bootstrap_holds_the_lock(): void
    {
        $lock = Cache::store('database')->lock('bootstrap-admin:initial-account', 120);
        $this->assertTrue($lock->get());

        try {
            $this->artisan('app:bootstrap-admin')
                ->expectsQuestion('Nome completo', 'Admin Concorrente')
                ->expectsQuestion('E-mail', 'admin-concorrente@example.test')
                ->expectsQuestion('Senha (mínimo de 8 caracteres)', 'SenhaSegura123!')
                ->expectsQuestion('Confirme a senha', 'SenhaSegura123!')
                ->expectsOutput('Outro processo está criando o administrador inicial. Tente novamente.')
                ->assertExitCode(1);

            $this->assertDatabaseCount('users', 0);
        } finally {
            $lock->release();
        }
    }
}
