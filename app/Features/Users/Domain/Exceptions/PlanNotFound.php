<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class PlanNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('El plan no existe.');
    }
}
