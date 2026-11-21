<?php

declare(strict_types=1);

namespace App\Features\Users\Application;

use App\Features\Users\Domain\Exceptions\InvalidProfilePhoto;
use App\Features\Users\Domain\Exceptions\PersonalAccessDenied;
use App\Features\Users\Domain\Exceptions\UserNotFound;
use App\Features\Users\Domain\PermissionName;
use App\Features\Users\Domain\PlatformUser;
use App\Features\Users\Domain\ProfilePhotoStore;
use App\Features\Users\Domain\UpdateOwnProfilePhotoData;
use App\Features\Users\Domain\UserRepository;

final class UpdateOwnProfilePhoto
{
    private const ALLOWED = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        private UserRepository $users,
        private ProfilePhotoStore $photos,
        private AuthorizeActor $access,
    ) {}

    public function handle(UpdateOwnProfilePhotoData $command): PlatformUser
    {
        $this->access->handle(
            $command->actorRole,
            $command->actorCompanyId,
            PermissionName::ProfileUpdateOwnPhoto,
            $command->actorCompanyId,
        );

        $user = $this->users->find($command->actorUserId);

        if ($user === null) {
            throw new UserNotFound;
        }

        if (! $user->belongsTo($command->actorCompanyId)) {
            throw new PersonalAccessDenied;
        }

        $extension = mb_strtolower(trim($command->extension));

        if (! in_array($extension, self::ALLOWED, true)) {
            throw new InvalidProfilePhoto('Usa una imagen JPG, PNG o WEBP.');
        }

        if ($command->binary === '' || strlen($command->binary) > 2_000_000) {
            throw new InvalidProfilePhoto('La foto debe pesar menos de 2 MB.');
        }

        $path = $this->photos->put($user->id(), $command->binary, $extension);
        $previous = $user->photoPath();
        $saved = $this->users->save($user->withPhotoPath($path));
        $this->photos->delete($previous);

        return $saved;
    }
}
