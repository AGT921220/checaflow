<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class GeofenceIsNotConfigured extends DomainException
{
    public function __construct()
    {
        parent::__construct('La sucursal exige geocerca, pero no está configurada.');
    }
}
