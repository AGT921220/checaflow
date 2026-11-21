<?php

declare(strict_types=1);

namespace App\Features\Branches\Domain\Exceptions;

use DomainException;

final class InvalidGeofenceRadius extends DomainException
{
    public function __construct()
    {
        parent::__construct('El radio de la geocerca debe ser de al menos 1 metro.');
    }
}
