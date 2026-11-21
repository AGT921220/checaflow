<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class UserIsNotPersisted extends DomainException
{
    public function __construct()
    {
        parent::__construct('El usuario todavía no está guardado.');
    }
}
