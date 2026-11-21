<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class PlanCodeRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('El plan es obligatorio.');
    }
}
