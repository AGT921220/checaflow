<?php

declare(strict_types=1);

namespace App\Features\Branches\Domain\Exceptions;

use DomainException;

final class BranchIsNotPersisted extends DomainException
{
    public function __construct()
    {
        parent::__construct('La sucursal todavía no está guardada.');
    }
}
