<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_opens_a_company_account_and_a_company_admin_is_forbidden(): void
    {
        $this->withoutVite();

        $admin = User::query()->create([
            'company_id' => null,
            'name' => 'Superadministrador',
            'nickname' => 'superadmin',
            'email' => 'superadmin@checaflow.test',
            'password' => 'admin',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $plan = Plan::query()->where('code', 'micro')->firstOrFail();

        $this->actingAs($admin)
            ->post('/platform/companies', [
                'company_name' => 'Taller Norte',
                'plan_id' => $plan->id,
                'max_employees' => 9,
                'first_name' => 'Nora',
                'last_name' => 'Garcia',
                'nickname' => 'nora-taller',
                'email' => '',
                'phone' => '5511223344',
                'password' => 'secret1',
            ])
            ->assertRedirect(route('platform.home'));

        $this->assertDatabaseHas('companies', [
            'name' => 'Taller Norte',
            'slug' => 'taller-norte',
            'plan_id' => $plan->id,
            'plan_code' => 'micro',
            'max_employees' => 9,
            'is_active' => true,
            'timezone' => 'America/Mexico_City',
        ]);

        $this->assertDatabaseHas('users', [
            'nickname' => 'nora-taller',
            'email' => null,
            'phone' => '5511223344',
            'first_name' => 'Nora',
            'last_name' => 'Garcia',
            'role' => 'company_admin',
            'is_active' => true,
        ]);

        $company = Company::query()->where('slug', 'taller-norte')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'nickname' => 'nora-taller',
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)
            ->get(route('platform.home'))
            ->assertOk()
            ->assertSee('Taller Norte')
            ->assertSee('Empresas registradas')
            ->assertSee('Empresa y administrador creados.');

        $manager = User::query()->where('nickname', 'nora-taller')->firstOrFail();

        $this->actingAs($manager)
            ->post('/platform/companies', [
                'company_name' => 'Otra',
                'plan_id' => $plan->id,
                'max_employees' => 5,
                'first_name' => 'Otro',
                'last_name' => 'Admin',
                'nickname' => 'otro',
                'password' => 'secret1',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('companies', ['slug' => 'otra']);
    }
}
