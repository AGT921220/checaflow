<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case CompanyAdmin = 'company_admin';
    case Hr = 'hr';
    case Employee = 'employee';

    public function managesCompany(): bool
    {
        return $this === self::CompanyAdmin || $this === self::Hr;
    }

    public function canBeGrantedByCompany(): bool
    {
        return $this === self::Hr || $this === self::Employee;
    }
}
