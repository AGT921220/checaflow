<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain;

use App\Features\Shared\Domain\Exceptions\InvalidPageQuery;

final readonly class PageQuery
{
    public int $page;

    public int $perPage;

    public function __construct(int $page = 1, int $perPage = 15)
    {
        if ($page < 1) {
            throw new InvalidPageQuery;
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidPageQuery;
        }

        $this->page = $page;
        $this->perPage = $perPage;
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
