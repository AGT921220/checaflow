<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class InactiveCompanyException extends DomainException
{
    public function __construct()
    {
        parent::__construct('La empresa no está activa.');
    }
}
