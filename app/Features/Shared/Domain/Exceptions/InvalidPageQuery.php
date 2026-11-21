<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain\Exceptions;

use DomainException;

final class InvalidPageQuery extends DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid page query.');
    }
}
