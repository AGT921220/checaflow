<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain\Exceptions;

use DomainException;

final class DuplicateWorkingDay extends DomainException
{
    public function __construct()
    {
        parent::__construct('Un día laboral no puede repetirse.');
    }
}
