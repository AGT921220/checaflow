<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class ForbiddenUserRole extends DomainException
{
    public function __construct()
    {
        parent::__construct('Ese rol no puede realizar esta operación.');
    }
}
