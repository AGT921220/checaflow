<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class CompanyIsNotPersisted extends DomainException
{
    public function __construct()
    {
        parent::__construct('La empresa todavía no está guardada.');
    }
}
