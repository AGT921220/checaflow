<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_setup_creates_the_platform_administrator(): void
    {
        $this->artisan('initial-setup')
            ->expectsOutputToContain('Administrador listo: superadmin')
            ->assertSuccessful();

        $admin = User::query()->where('nickname', 'superadmin')->first();

        $this->assertNotNull($admin);
        $this->assertSame('super_admin', $admin->role);
        $this->assertNull($admin->company_id);
        $this->assertTrue(Hash::check('admin', $admin->password));

        $this->withoutVite();

        $this->post('/login', [
            'nickname' => 'superadmin',
            'password' => 'admin',
        ])->assertRedirect(route('platform.home'));

        $this->get(route('platform.home'))
            ->assertOk()
            ->assertSee('Plataforma')
            ->assertSee('superadmin')
            ->assertSee('Todavía no hay empresas.')
            ->assertDontSee('Asistencia del día');

        $this->artisan('initial-setup')
            ->expectsOutputToContain('El administrador ya existe.')
            ->assertSuccessful();

        $this->assertSame(1, User::query()->where('nickname', 'superadmin')->count());
    }
}
