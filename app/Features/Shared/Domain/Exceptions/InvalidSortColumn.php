<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain\Exceptions;

use DomainException;

final class InvalidSortColumn extends DomainException
{
    public function __construct(string $column)
    {
        parent::__construct("La columna [{$column}] no se puede usar para ordenar.");
    }
}
