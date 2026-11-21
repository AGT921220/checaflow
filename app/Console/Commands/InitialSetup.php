<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

final class InitialSetup extends Command
{
    protected $signature = 'initial-setup';

    protected $description = 'Create the platform administrator.';

    public function handle(): int
    {
        $existing = User::query()->where('nickname', 'superadmin')->first();

        if ($existing !== null) {
            $this->components->info('El administrador ya existe.');

            return self::SUCCESS;
        }

        User::query()->create([
            'company_id' => null,
            'name' => 'Superadministrador',
            'nickname' => 'superadmin',
            'email' => 'superadmin@checaflow.test',
            'password' => 'admin',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->components->info('Administrador listo: superadmin');

        return self::SUCCESS;
    }
}
