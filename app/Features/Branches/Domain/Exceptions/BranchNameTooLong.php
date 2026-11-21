<?php

declare(strict_types=1);

namespace App\Features\Branches\Domain\Exceptions;

use DomainException;

final class BranchNameTooLong extends DomainException
{
    public function __construct()
    {
        parent::__construct('El nombre de la sucursal no puede tener más de 255 caracteres.');
    }
}
