<?php

declare(strict_types=1);

namespace App\Http\Search;

use Illuminate\Http\Request;

/**
 * Reads page, per_page, sort and filterable keys from request using config/{entity}.php.
 */
final class SearchListInput
{
    public function __construct(private Request $request) {}

    /**
     * @return array{
     *     sortColumn: string,
     *     sortDirection: string,
     *     page: int,
     *     perPage: int,
     *     allowedSortColumns: list<string>,
     *     maxPerPage: int
     * }
     */
    public function paging(string $configKey): array
    {
        /** @var list<string> $sortable */
        $sortable = array_values(config($configKey.'.sortable', []));
        $defaultSort = (string) config($configKey.'.default_sort', $sortable[0] ?? 'id');
        $defaultDirection = (string) config($configKey.'.default_direction', 'asc');
        $defaultPerPage = (int) config($configKey.'.default_per_page', 15);
        $maxPerPage = (int) config($configKey.'.max_per_page', 100);

        $sortColumn = (string) $this->request->input('sort', $defaultSort);
        $sortDirection = (string) $this->request->input('direction', $defaultDirection);
        $page = max(1, $this->request->integer('page', 1));
        $perPage = $this->request->integer('per_page', $defaultPerPage);

        if ($perPage < 1) {
            $perPage = $defaultPerPage;
        }

        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        return [
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'page' => $page,
            'perPage' => $perPage,
            'allowedSortColumns' => $sortable,
            'maxPerPage' => $maxPerPage,
        ];
    }

    public function allowsFilter(string $configKey, string $field): bool
    {
        /** @var list<string> $filterable */
        $filterable = array_values(config($configKey.'.filterable', []));

        return in_array($field, $filterable, true);
    }

    public function optionalInt(string $configKey, string $field): ?int
    {
        if (! $this->allowsFilter($configKey, $field)) {
            return null;
        }

        if (! $this->request->filled($field)) {
            return null;
        }

        $value = $this->request->integer($field);

        if ($value < 1) {
            return null;
        }

        return $value;
    }

    public function optionalBool(string $configKey, string $field): ?bool
    {
        if (! $this->allowsFilter($configKey, $field)) {
            return null;
        }

        if (! $this->request->has($field) || $this->request->input($field) === '' || $this->request->input($field) === null) {
            return null;
        }

        return $this->request->boolean($field);
    }

    public function optionalString(string $configKey, string $field): ?string
    {
        if (! $this->allowsFilter($configKey, $field)) {
            return null;
        }

        if (! $this->request->filled($field)) {
            return null;
        }

        return $this->request->string($field)->toString();
    }
}
