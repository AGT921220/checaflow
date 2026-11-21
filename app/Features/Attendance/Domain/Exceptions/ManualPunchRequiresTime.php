<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class ManualPunchRequiresTime extends DomainException
{
    public function __construct()
    {
        parent::__construct('Un ajuste manual exige la hora de la marca.');
    }
}
