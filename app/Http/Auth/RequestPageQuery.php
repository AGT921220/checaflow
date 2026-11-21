<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Features\Shared\Domain\Exceptions\InvalidPageQuery;
use App\Features\Shared\Domain\PageQuery;
use Illuminate\Http\Request;

final class RequestPageQuery
{
    public function from(Request $request, int $defaultPerPage = 15): PageQuery
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = $request->integer('per_page', $defaultPerPage);

        try {
            return new PageQuery($page, $perPage);
        } catch (InvalidPageQuery) {
            return new PageQuery(1, $defaultPerPage);
        }
    }
}
