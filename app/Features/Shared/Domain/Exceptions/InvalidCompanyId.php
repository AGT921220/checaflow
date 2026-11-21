<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain\Exceptions;

use DomainException;

final class InvalidCompanyId extends DomainException
{
    public function __construct()
    {
        parent::__construct('El id de la empresa debe ser un número positivo.');
    }
}
