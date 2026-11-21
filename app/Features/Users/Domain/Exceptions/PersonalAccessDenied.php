<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class PersonalAccessDenied extends DomainException
{
    public function __construct()
    {
        parent::__construct('Solo puedes registrar o consultar tu propia asistencia.');
    }
}
