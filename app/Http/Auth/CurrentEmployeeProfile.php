<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Models\EmployeeProfile;
use Illuminate\Http\Request;

final class CurrentEmployeeProfile
{
    public function id(Request $request): int
    {
        $profileId = EmployeeProfile::query()
            ->where('company_id', $request->user()?->company_id)
            ->where('user_id', $request->user()?->id)
            ->where('is_active', true)
            ->value('id');

        if ($profileId === null) {
            abort(403);
        }

        return (int) $profileId;
    }
}
