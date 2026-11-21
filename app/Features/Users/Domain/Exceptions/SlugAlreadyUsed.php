<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class SlugAlreadyUsed extends DomainException
{
    public function __construct()
    {
        parent::__construct('Ese identificador ya está en uso.');
    }
}
