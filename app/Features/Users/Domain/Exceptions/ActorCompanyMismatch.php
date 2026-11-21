<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class ActorCompanyMismatch extends DomainException
{
    public function __construct()
    {
        parent::__construct('No puedes operar datos de otra empresa.');
    }
}
