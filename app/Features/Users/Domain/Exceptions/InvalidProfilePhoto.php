<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class InvalidProfilePhoto extends DomainException
{
    public function __construct(string $message = 'La foto de perfil no es válida.')
    {
        parent::__construct($message);
    }
}
