<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class BranchLimitBelowActiveCount extends DomainException
{
    public function __construct()
    {
        parent::__construct('El tope de sucursales no puede ser menor al número de sucursales activas.');
    }
}
