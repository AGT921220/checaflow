<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain;

use App\Features\Shared\Domain\Exceptions\InvalidCompanyId;

final readonly class CompanyId
{
    public function __construct(public int $value)
    {
        if ($this->value < 1) {
            throw new InvalidCompanyId;
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
