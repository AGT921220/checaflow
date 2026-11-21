<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class InvalidCompanyTimezone extends DomainException
{
    public function __construct(string $timezone)
    {
        parent::__construct("La zona horaria [{$timezone}] no es válida.");
    }
}
