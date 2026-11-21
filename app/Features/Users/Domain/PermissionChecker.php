<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

interface PermissionChecker
{
    public function allows(UserRole $role, PermissionName $permission): bool;
}
