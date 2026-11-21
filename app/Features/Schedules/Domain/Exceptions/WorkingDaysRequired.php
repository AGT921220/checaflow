<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain\Exceptions;

use DomainException;

final class WorkingDaysRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('Un horario necesita al menos un día laboral.');
    }
}
