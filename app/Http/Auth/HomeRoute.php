<?php

declare(strict_types=1);

namespace App\Http\Auth;

use RuntimeException;

final class HomeRoute
{
    private const ROUTES = [
        'employee' => 'attendance.today',
        'company_admin' => 'attendance.page',
        'hr' => 'attendance.page',
        'super_admin' => 'attendance.page',
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
