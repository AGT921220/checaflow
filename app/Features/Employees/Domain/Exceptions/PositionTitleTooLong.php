<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class PositionTitleTooLong extends DomainException
{
    public function __construct()
    {
        parent::__construct('El puesto no puede tener más de 255 caracteres.');
    }
}
