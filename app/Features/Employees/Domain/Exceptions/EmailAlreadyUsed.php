<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class EmailAlreadyUsed extends DomainException
{
    public function __construct()
    {
        parent::__construct('Ese correo ya está registrado.');
    }
}
