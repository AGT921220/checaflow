<?php

declare(strict_types=1);

namespace App\Features\Users\Application;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Exceptions\CompanyNotFound;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\CreateCompanyAdminData;
use App\Features\Users\Domain\Exceptions\EmailAlreadyUsed;
use App\Features\Users\Domain\Exceptions\InactiveCompanyException;
use App\Features\Users\Domain\Exceptions\NicknameAlreadyUsed;
use App\Features\Users\Domain\Exceptions\UnauthorizedCompanyAdminCreation;
use App\Features\Users\Domain\PermissionName;
use App\Features\Users\Domain\PlatformUser;
use App\Features\Users\Domain\UserRepository;
use App\Features\Users\Domain\UserRole;

final class CreateCompanyAdmin
{
    public function __construct(
        private UserRepository $users,
        private CompanyRepository $companies,
        private AuthorizeActor $access,
    ) {}

    public function handle(CreateCompanyAdminData $command): PlatformUser
    {
        $this->access->handle($command->actorRole, null, PermissionName::CompanyAdminsManage);

        if ($command->actorRole !== UserRole::SuperAdmin) {
            throw new UnauthorizedCompanyAdminCreation;
        }

        $this->assertActiveCompany($command->targetCompanyId);

        $user = PlatformUser::register(
            $command->targetCompanyId,
            $command->firstName,
            $command->lastName,
            $command->nickname,
            $command->email,
            $command->phone,
            UserRole::CompanyAdmin,
            true,
            $command->plainPassword,
        );

        $this->assertNicknameIsFree($user->nickname());
        $this->assertEmailIsFree($user->email());

        return $this->users->save($user);
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

    private function assertNicknameIsFree(string $nickname): void
    {
        if ($this->users->findByNickname($nickname) !== null) {
            throw new NicknameAlreadyUsed;
        }
    }

    private function assertEmailIsFree(?string $email): void
    {
        if ($email === null) {
            return;
        }

        if ($this->users->findByEmail($email) !== null) {
            throw new EmailAlreadyUsed;
        }
    }
}
