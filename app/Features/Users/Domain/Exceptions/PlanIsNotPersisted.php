<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class PlanIsNotPersisted extends DomainException
{
    public function __construct()
    {
        parent::__construct('El plan todavía no está guardado.');
    }
}
