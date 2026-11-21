<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain\Exceptions;

use DomainException;

final class ScheduleDoesNotBelongToCompany extends DomainException
{
    public function __construct()
    {
        parent::__construct('El horario no pertenece a esta empresa.');
    }
}
