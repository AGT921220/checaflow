<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class InvalidAttendanceRange extends DomainException
{
    public function __construct(string $message = 'El rango de fechas no es válido.')
    {
        parent::__construct($message);
    }
}
