<?php

declare(strict_types=1);

namespace App\Features\Branches\Domain\Exceptions;

use DomainException;

final class BranchNameRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('El nombre de la sucursal es obligatorio.');
    }
}
