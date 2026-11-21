<?php

declare(strict_types=1);

namespace App\Features\Users\Application;

use App\Features\Users\Domain\Exceptions\ActorCompanyMismatch;
use App\Features\Users\Domain\Exceptions\ForbiddenUserRole;
use App\Features\Users\Domain\Exceptions\UserNotFound;
use App\Features\Users\Domain\PermissionName;
use App\Features\Users\Domain\PlatformUser;
use App\Features\Users\Domain\ToggleUserStatusData;
use App\Features\Users\Domain\UserRepository;

final class ToggleUserStatus
{
    public function __construct(
        private UserRepository $users,
        private AuthorizeActor $access,
    ) {}

    public function handle(ToggleUserStatusData $command): PlatformUser
    {
        $this->access->handle(
            $command->actorRole,
            $command->actorCompanyId,
            PermissionName::UsersManage,
            $command->actorCompanyId,
        );

        if (! $command->actorRole->managesCompany()) {
            throw new ForbiddenUserRole;
        }

        $user = $this->users->find($command->userId);

        if ($user === null) {
            throw new UserNotFound;
        }

        if ($user->companyId() === null || ! $user->companyId()->equals($command->actorCompanyId)) {
            throw new ActorCompanyMismatch;
        }

        if (! $user->role()->canBeGrantedByCompany()) {
            throw new ForbiddenUserRole;
        }

        return $this->users->save($user->withActive($command->isActive));
    }
}
