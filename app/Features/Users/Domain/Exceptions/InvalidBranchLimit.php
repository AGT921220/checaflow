<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class InvalidBranchLimit extends DomainException
{
    public function __construct(string $message = 'El tope de sucursales debe ser al menos 1, o nulo si es ilimitado.')
    {
        parent::__construct($message);
    }
}
