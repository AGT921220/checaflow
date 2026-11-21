<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class CompanySlugRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('El identificador de la empresa es obligatorio.');
    }
}
