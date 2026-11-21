<?php

declare(strict_types=1);

namespace App\Features\Branches\Domain\Exceptions;

use DomainException;

final class GeofenceIsNotConfigured extends DomainException
{
    public function __construct()
    {
        parent::__construct('Una geocerca obligatoria necesita ubicación y radio en metros.');
    }
}
