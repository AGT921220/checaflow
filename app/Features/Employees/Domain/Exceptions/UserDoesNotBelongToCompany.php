<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class UserDoesNotBelongToCompany extends DomainException
{
    public function __construct()
    {
        parent::__construct('El usuario no es un miembro activo de esta empresa.');
    }
}
