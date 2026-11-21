<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use App\Features\Attendance\Domain\PunchType;
use DomainException;

final class InvalidPunchSequence extends DomainException
{
    public function __construct(PunchType $expected, PunchType $actual)
    {
        parent::__construct("Se esperaba {$expected->value} y se recibió {$actual->value}.");
    }
}
