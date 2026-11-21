<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class PermissionDenied extends DomainException
{
    public function __construct()
    {
        parent::__construct('No tienes permiso para realizar esta operación.');
    }
}
