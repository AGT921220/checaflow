<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Shared\Domain\CompanyId;
use DateTimeImmutable;

interface CompanyClock
{
    public function timezone(CompanyId $companyId): ?string;

    public function now(): DateTimeImmutable;
}
