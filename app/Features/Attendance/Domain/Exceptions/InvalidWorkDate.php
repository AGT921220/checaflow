<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class InvalidWorkDate extends DomainException
{
    public function __construct(string $value)
    {
        parent::__construct("La fecha [{$value}] no es una fecha de calendario.");
    }
}
