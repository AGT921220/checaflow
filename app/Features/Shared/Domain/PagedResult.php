<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain;

final readonly class PagedResult
{
    /**
     * @param  list<mixed>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function lastPage(): int
    {
        if ($this->total < 1) {
            return 1;
        }

        return (int) max(1, (int) ceil($this->total / $this->perPage));
    }

    /**
     * @return array{total: int, page: int, per_page: int, last_page: int}
     */
    public function meta(): array
    {
        return [
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'last_page' => $this->lastPage(),
        ];
    }
}
