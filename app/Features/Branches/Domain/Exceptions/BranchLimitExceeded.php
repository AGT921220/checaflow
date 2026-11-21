<?php

declare(strict_types=1);

namespace App\Features\Branches\Domain\Exceptions;

use DomainException;

final class BranchLimitExceeded extends DomainException
{
    public function __construct()
    {
        parent::__construct('La empresa alcanzó el límite de sucursales activas.');
    }
}
