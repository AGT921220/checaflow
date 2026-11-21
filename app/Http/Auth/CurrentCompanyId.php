<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Features\Shared\Domain\CompanyId;
use Illuminate\Http\Request;

final class CurrentCompanyId
{
    public function from(Request $request): CompanyId
    {
        $companyId = $request->user()?->company_id;

        if ($companyId === null) {
            abort(403);
        }

        return new CompanyId((int) $companyId);
    }
}
