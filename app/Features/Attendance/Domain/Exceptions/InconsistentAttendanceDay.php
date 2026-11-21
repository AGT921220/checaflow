<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class InconsistentAttendanceDay extends DomainException
{
    public function __construct()
    {
        parent::__construct('Las marcas se saltan un paso del ciclo diario.');
    }
}
