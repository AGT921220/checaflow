<?php

declare(strict_types=1);

namespace App\Features\Branches\Domain\Exceptions;

use DomainException;

final class BranchNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('No se encontró la sucursal en esta empresa.');
    }
}
