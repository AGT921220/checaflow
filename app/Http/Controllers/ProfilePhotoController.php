<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Features\Users\Application\UpdateOwnProfilePhoto;
use App\Features\Users\Domain\UpdateOwnProfilePhotoData;
use App\Features\Users\Domain\UserRole;
use App\Http\Auth\CurrentCompanyId;
use App\Http\Auth\HomeRoute;
use App\Http\Requests\StoreProfilePhotoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

final class ProfilePhotoController extends Controller
{
    public function store(
        StoreProfilePhotoRequest $request,
        UpdateOwnProfilePhoto $updatePhoto,
        CurrentCompanyId $company,
        HomeRoute $home,
    ): RedirectResponse {
        $role = $request->user()?->role;
        $userId = $request->user()?->id;

        if (! is_string($role) || ! is_int($userId)) {
            abort(403);
        }

        $photo = $request->file('photo');

        if (! $photo instanceof UploadedFile) {
            abort(422);
        }

        $binary = file_get_contents($photo->getRealPath());

        if ($binary === false) {
            abort(422);
        }

        $updatePhoto->handle(new UpdateOwnProfilePhotoData(
            UserRole::from($role),
            $company->from($request),
            $userId,
            $binary,
            $photo->extension() ?: 'jpg',
        ));

        return redirect()
            ->route($home->nameFor($role))
            ->with('status', 'Foto de perfil actualizada.');
    }
}
