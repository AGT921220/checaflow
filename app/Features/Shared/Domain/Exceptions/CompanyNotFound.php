<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain\Exceptions;

use DomainException;

final class CompanyNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('No se encontró la empresa.');
    }
}
