<?php

declare(strict_types=1);

namespace App\Features\Users\Application;

use App\Features\Users\Domain\ActivateCompanyAdminData;
use App\Features\Users\Domain\Exceptions\ActorCompanyMismatch;
use App\Features\Users\Domain\Exceptions\ForbiddenUserRole;
use App\Features\Users\Domain\Exceptions\UnauthorizedCompanyAdminCreation;
use App\Features\Users\Domain\Exceptions\UserNotFound;
use App\Features\Users\Domain\PermissionName;
use App\Features\Users\Domain\PlatformUser;
use App\Features\Users\Domain\UserRepository;
use App\Features\Users\Domain\UserRole;

final class ActivateCompanyAdmin
{
    public function __construct(
        private UserRepository $users,
        private AuthorizeActor $access,
    ) {}

    public function handle(ActivateCompanyAdminData $command): PlatformUser
    {
        $this->access->handle($command->actorRole, null, PermissionName::CompanyAdminsManage);

        if ($command->actorRole !== UserRole::SuperAdmin) {
            throw new UnauthorizedCompanyAdminCreation;
        }

        $user = $this->users->find($command->userId);

        if ($user === null) {
            throw new UserNotFound;
        }

        if ($user->role() !== UserRole::CompanyAdmin) {
            throw new ForbiddenUserRole;
        }

        if ($user->companyId() === null) {
            throw new ActorCompanyMismatch;
        }

        return $this->users->save($user->withActive($command->isActive));
    }
}
