<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain\Exceptions;

use DomainException;

final class ScheduleIsNotPersisted extends DomainException
{
    public function __construct()
    {
        parent::__construct('El horario todavía no está guardado.');
    }
}
