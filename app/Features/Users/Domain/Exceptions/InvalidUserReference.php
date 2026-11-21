<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class InvalidUserReference extends DomainException
{
    public function __construct()
    {
        parent::__construct('La referencia del usuario no es válida.');
    }
}
