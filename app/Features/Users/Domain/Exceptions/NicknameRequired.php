<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class NicknameRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('El nickname es obligatorio.');
    }
}
