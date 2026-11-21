<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain\Exceptions;

use DomainException;

final class InvalidTimeOfDay extends DomainException
{
    public function __construct(string $value)
    {
        parent::__construct("La hora [{$value}] no es válida.");
    }
}
