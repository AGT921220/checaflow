<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class AttendanceDayAlreadyClosed extends DomainException
{
    public function __construct()
    {
        parent::__construct('Esta fecha ya tiene la marca de salida.');
    }
}
