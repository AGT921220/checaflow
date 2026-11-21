<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class PunchOutsideGeofence extends DomainException
{
    public function __construct()
    {
        parent::__construct('La marca está fuera de la geocerca de la sucursal.');
    }
}
