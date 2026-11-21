<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain\Exceptions;

use DomainException;

final class ScheduleNameTooLong extends DomainException
{
    public function __construct()
    {
        parent::__construct('El nombre del horario no puede tener más de 255 caracteres.');
    }
}
