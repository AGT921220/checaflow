<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class UserNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('No se encontró el usuario.');
    }
}
