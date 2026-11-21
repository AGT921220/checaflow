<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain\Exceptions;

use DomainException;

final class ScheduleNameRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('El nombre del horario es obligatorio.');
    }
}
