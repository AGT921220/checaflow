<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class UnauthorizedCompanyAdminCreation extends DomainException
{
    public function __construct()
    {
        parent::__construct('Solo un super administrador puede gestionar administradores de empresa.');
    }
}
