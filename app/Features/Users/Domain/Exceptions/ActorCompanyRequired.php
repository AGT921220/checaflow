<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class ActorCompanyRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('Tu usuario debe pertenecer a una empresa.');
    }
}
