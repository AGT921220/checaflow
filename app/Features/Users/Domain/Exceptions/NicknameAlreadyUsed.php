<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class NicknameAlreadyUsed extends DomainException
{
    public function __construct()
    {
        parent::__construct('Ese nickname ya está en uso.');
    }
}
