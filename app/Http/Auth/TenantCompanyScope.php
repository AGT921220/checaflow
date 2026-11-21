<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Features\Shared\Domain\CompanyId;
use Illuminate\Http\Request;

final class TenantCompanyScope
{
    public function __construct(private CurrentCompanyId $current) {}

    public function optional(Request $request): ?CompanyId
    {
        if ($request->user()?->role === 'super_admin') {
            if (! $request->filled('company_id')) {
                return null;
            }

            $value = $request->integer('company_id');

            if ($value < 1) {
                abort(404);
            }

            return new CompanyId($value);
        }

        return $this->current->from($request);
    }

    public function forMutation(Request $request): CompanyId
    {
        if ($request->user()?->role === 'super_admin') {
            abort(403);
        }

        return $this->current->from($request);
    }

    public function isPlatform(Request $request): bool
    {
        return $request->user()?->role === 'super_admin';
    }
}
