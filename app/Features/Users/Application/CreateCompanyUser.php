<?php

declare(strict_types=1);

namespace App\Features\Users\Application;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Exceptions\CompanyNotFound;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\CreateCompanyUserData;
use App\Features\Users\Domain\Exceptions\EmailAlreadyUsed;
use App\Features\Users\Domain\Exceptions\ForbiddenUserRole;
use App\Features\Users\Domain\Exceptions\InactiveCompanyException;
use App\Features\Users\Domain\Exceptions\NicknameAlreadyUsed;
use App\Features\Users\Domain\PermissionName;
use App\Features\Users\Domain\PlatformUser;
use App\Features\Users\Domain\UserRepository;

final class CreateCompanyUser
{
    public function __construct(
        private UserRepository $users,
        private CompanyRepository $companies,
        private AuthorizeActor $access,
    ) {}

    public function handle(CreateCompanyUserData $command): PlatformUser
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

        if (! $command->role->canBeGrantedByCompany()) {
            throw new ForbiddenUserRole;
        }

        $this->assertActiveCompany($command->actorCompanyId);

        $email = mb_strtolower(trim($command->email));
        $nickname = $this->nicknameFromEmail($email);
        $this->assertEmailIsFree($email);
        $this->assertNicknameIsFree($nickname);

        return $this->users->save(PlatformUser::register(
            $command->actorCompanyId,
            $command->name,
            '',
            $nickname,
            $email,
            null,
            $command->role,
            $command->isActive,
            null,
        ));
    }

    private function assertActiveCompany(CompanyId $companyId): void
    {
        $company = $this->companies->find($companyId);

        if ($company === null) {
            throw new CompanyNotFound;
        }

        if (! $company->isActive()) {
            throw new InactiveCompanyException;
        }
    }

    private function assertEmailIsFree(string $email): void
    {
        if ($this->users->findByEmail($email) !== null) {
            throw new EmailAlreadyUsed;
        }
    }

    private function assertNicknameIsFree(string $nickname): void
    {
        if ($this->users->findByNickname($nickname) !== null) {
            throw new NicknameAlreadyUsed;
        }
    }

    private function nicknameFromEmail(string $email): string
    {
        $local = explode('@', $email, 2)[0] ?? '';

        return mb_strtolower(trim($local));
    }
}
