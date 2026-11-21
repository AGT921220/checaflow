<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain\Exceptions;

use DomainException;

final class InvalidGeoPoint extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
