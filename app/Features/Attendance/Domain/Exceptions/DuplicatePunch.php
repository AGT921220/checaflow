<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use App\Features\Attendance\Domain\PunchType;
use DomainException;

final class DuplicatePunch extends DomainException
{
    public function __construct(PunchType $type)
    {
        parent::__construct("La marca {$type->value} ya está registrada para esta fecha.");
    }
}
