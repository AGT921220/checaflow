<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain\Exceptions;

use DomainException;

final class ScheduleNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('No se encontró el horario en esta empresa.');
    }
}
