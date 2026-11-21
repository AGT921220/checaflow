<?php

declare(strict_types=1);

namespace App\Features\Attendance\Infrastructure;

use App\Features\Attendance\Domain\CompanyClock;
use App\Features\Shared\Domain\CompanyId;
use App\Models\Company;
use DateTimeImmutable;
use DateTimeZone;

final class EloquentCompanyClock implements CompanyClock
{
    public function timezone(CompanyId $companyId): ?string
    {
        $timezone = Company::query()->whereKey($companyId->value)->value('timezone');

        if (! is_string($timezone) || $timezone === '') {
            return null;
        }

        return $timezone;
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
