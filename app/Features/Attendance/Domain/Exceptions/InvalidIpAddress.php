<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class InvalidIpAddress extends DomainException
{
    public function __construct()
    {
        parent::__construct('La dirección IP no es válida.');
    }
}
