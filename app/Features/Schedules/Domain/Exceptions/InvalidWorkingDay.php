<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain\Exceptions;

use DomainException;

final class InvalidWorkingDay extends DomainException
{
    public function __construct(string $day)
    {
        parent::__construct("El día laboral [{$day}] no es válido.");
    }
}
