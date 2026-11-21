<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain\Exceptions;

use DomainException;

final class InvalidTolerance extends DomainException
{
    public function __construct()
    {
        parent::__construct('La tolerancia y los minutos de comida deben estar entre 0 y 65535.');
    }
}
