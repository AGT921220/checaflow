<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain\Exceptions;

use DomainException;

final class InvalidSortDirection extends DomainException
{
    public function __construct(string $direction)
    {
        parent::__construct("La dirección de orden [{$direction}] no está permitida.");
    }
}
