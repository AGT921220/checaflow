<?php

declare(strict_types=1);

namespace App\Http\Auth;

use RuntimeException;

final class HomeRoute
{
    private const ROUTES = [
        'employee' => 'attendance.today',
        'company_admin' => 'admin.attendance.index',
        'hr' => 'admin.attendance.index',
        'super_admin' => 'platform.home',
    ];

    public function nameFor(string $role): string
    {
        $name = self::ROUTES[$role] ?? null;

        if ($name === null) {
            throw new RuntimeException('Unknown role.');
        }

        return $name;
    }
}
