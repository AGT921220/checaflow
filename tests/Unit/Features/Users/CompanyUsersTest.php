<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Users;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Exceptions\CompanyNotFound;
use App\Features\Users\Application\ActivateCompanyAdmin;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Application\CreateCompanyAdmin;
use App\Features\Users\Application\CreateCompanyUser;
use App\Features\Users\Application\ToggleUserStatus;
use App\Features\Users\Domain\ActivateCompanyAdminData;
use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\CreateCompanyAdminData;
use App\Features\Users\Domain\CreateCompanyUserData;
use App\Features\Users\Domain\Exceptions\ActorCompanyMismatch;
use App\Features\Users\Domain\Exceptions\EmailAlreadyUsed;
use App\Features\Users\Domain\Exceptions\ForbiddenUserRole;
use App\Features\Users\Domain\Exceptions\InactiveCompanyException;
use App\Features\Users\Domain\Exceptions\PermissionDenied;
use App\Features\Users\Domain\Exceptions\UserIsNotPersisted;
use App\Features\Users\Domain\PlatformUser;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\ToggleUserStatusData;
use App\Features\Users\Domain\UserRepository;
use App\Features\Users\Domain\UserRole;
use PHPUnit\Framework\TestCase;

final class CompanyUsersTest extends TestCase
{
    public function test_super_admin_creates_a_company_admin_in_an_active_company(): void
    {
        $companyId = new CompanyId(4);
        $users = new MemoryUsers;
        $action = new CreateCompanyAdmin($users, new MemoryCompanies([4 => $this->company($companyId)]), $this->access());

        $admin = $action->handle($this->adminCommand($companyId));

        $this->assertSame(UserRole::CompanyAdmin, $admin->role());
        $this->assertTrue($admin->belongsTo($companyId));
        $this->assertSame('ada@acme.test', $admin->email());
        $this->assertSame('ada', $admin->nickname());
        $this->assertSame('plain-secret', $admin->plainPassword());
        $this->assertTrue($admin->isActive());
    }

    public function test_another_role_cannot_create_a_company_admin(): void
    {
        $users = new MemoryUsers;
        $action = new CreateCompanyAdmin($users, new MemoryCompanies([4 => $this->company(new CompanyId(4))]), $this->access());

        $this->expectException(PermissionDenied::class);

        $action->handle($this->adminCommand(new CompanyId(4), UserRole::CompanyAdmin));
    }

    public function test_an_inactive_company_rejects_a_company_admin(): void
    {
        $action = new CreateCompanyAdmin(new MemoryUsers, new MemoryCompanies([
            4 => $this->company(new CompanyId(4), false),
        ]), $this->access());

        $this->expectException(InactiveCompanyException::class);

        $action->handle($this->adminCommand(new CompanyId(4)));
    }

    public function test_a_missing_company_rejects_a_company_admin(): void
    {
        $action = new CreateCompanyAdmin(new MemoryUsers, new MemoryCompanies([]), $this->access());

        $this->expectException(CompanyNotFound::class);

        $action->handle($this->adminCommand(new CompanyId(4)));
    }

    public function test_a_repeated_email_is_rejected(): void
    {
        $companyId = new CompanyId(4);
        $users = new MemoryUsers;
        $users->save($this->user($companyId, 'Ada', 'Lovelace', 'ada', 'ada@acme.test', UserRole::CompanyAdmin));
        $action = new CreateCompanyAdmin($users, new MemoryCompanies([4 => $this->company($companyId)]), $this->access());

        $this->expectException(EmailAlreadyUsed::class);

        $action->handle(new CreateCompanyAdminData(
            UserRole::SuperAdmin,
            $companyId,
            'Other',
            'Person',
            'other',
            'ADA@acme.test',
            null,
            'plain-secret',
        ));
    }

    public function test_company_admin_cannot_create_a_super_admin_or_another_company_admin(): void
    {
        $companyId = new CompanyId(4);
        $users = new MemoryUsers;
        $action = new CreateCompanyUser($users, new MemoryCompanies([4 => $this->company($companyId)]), $this->access());

        try {
            $action->handle(new CreateCompanyUserData(
                UserRole::CompanyAdmin,
                $companyId,
                'Root',
                'root@acme.test',
                UserRole::SuperAdmin,
                true,
            ));
            $this->fail('A company admin must not create a super admin.');
        } catch (ForbiddenUserRole) {
            $this->assertSame([], $users->saved);
        }
    }

    public function test_company_admin_cannot_create_another_company_admin(): void
    {
        $companyId = new CompanyId(4);
        $action = new CreateCompanyUser(new MemoryUsers, new MemoryCompanies([4 => $this->company(new CompanyId(4))]), $this->access());

        $this->expectException(ForbiddenUserRole::class);

        $action->handle(new CreateCompanyUserData(
            UserRole::CompanyAdmin,
            $companyId,
            'Peer',
            'peer@acme.test',
            UserRole::CompanyAdmin,
            true,
        ));
    }

    public function test_company_admin_creates_hr_and_employee_in_their_company(): void
    {
        $companyId = new CompanyId(4);
        $users = new MemoryUsers;
        $action = new CreateCompanyUser($users, new MemoryCompanies([4 => $this->company($companyId)]), $this->access());

        $hr = $action->handle(new CreateCompanyUserData(
            UserRole::CompanyAdmin,
            $companyId,
            'Hana',
            'hana@acme.test',
            UserRole::Hr,
            true,
        ));
        $employee = $action->handle(new CreateCompanyUserData(
            UserRole::CompanyAdmin,
            $companyId,
            'Eva',
            'eva@acme.test',
            UserRole::Employee,
            true,
        ));

        $this->assertSame(UserRole::Hr, $hr->role());
        $this->assertTrue($hr->belongsTo($companyId));
        $this->assertNull($hr->plainPassword());
        $this->assertSame(UserRole::Employee, $employee->role());
        $this->assertTrue($employee->belongsTo($companyId));
    }

    public function test_super_admin_activates_a_company_admin(): void
    {
        $companyId = new CompanyId(4);
        $users = new MemoryUsers;
        $admin = $users->save($this->user($companyId, 'Ada', 'Lovelace', 'ada', 'ada@acme.test', UserRole::CompanyAdmin));
        $action = new ActivateCompanyAdmin($users, $this->access());

        $updated = $action->handle(new ActivateCompanyAdminData(UserRole::SuperAdmin, $admin->id(), false));

        $this->assertFalse($updated->isActive());
        $this->assertSame(UserRole::CompanyAdmin, $updated->role());
    }

    public function test_a_company_admin_cannot_activate_another_company_admin(): void
    {
        $companyId = new CompanyId(4);
        $users = new MemoryUsers;
        $admin = $users->save($this->user($companyId, 'Ada', 'Lovelace', 'ada', 'ada@acme.test', UserRole::CompanyAdmin));

        $this->expectException(PermissionDenied::class);

        (new ActivateCompanyAdmin($users, $this->access()))->handle(new ActivateCompanyAdminData(UserRole::CompanyAdmin, $admin->id(), false));
    }

    public function test_company_admin_toggles_hr_in_the_same_company(): void
    {
        $companyId = new CompanyId(4);
        $users = new MemoryUsers;
        $hr = $users->save($this->user($companyId, 'Hana', 'Hr', 'hana', 'hana@acme.test', UserRole::Hr));

        $updated = (new ToggleUserStatus($users, $this->access()))->handle(new ToggleUserStatusData(
            UserRole::CompanyAdmin,
            $companyId,
            $hr->id(),
            false,
        ));

        $this->assertFalse($updated->isActive());
    }

    public function test_company_admin_cannot_toggle_a_company_admin_or_another_company(): void
    {
        $companyId = new CompanyId(4);
        $users = new MemoryUsers;
        $admin = $users->save($this->user($companyId, 'Ada', 'Lovelace', 'ada', 'ada@acme.test', UserRole::CompanyAdmin));
        $foreign = $users->save($this->user(new CompanyId(9), 'Eva', 'Other', 'eva', 'eva@other.test', UserRole::Employee));
        $action = new ToggleUserStatus($users, $this->access());

        try {
            $action->handle(new ToggleUserStatusData(UserRole::CompanyAdmin, $companyId, $admin->id(), false));
            $this->fail('A company admin must not change another company admin.');
        } catch (ForbiddenUserRole) {
            $this->assertTrue($users->find($admin->id())->isActive());
        }

        $this->expectException(ActorCompanyMismatch::class);

        $action->handle(new ToggleUserStatusData(UserRole::CompanyAdmin, $companyId, $foreign->id(), false));
    }

    private function access(): AuthorizeActor
    {
        return new AuthorizeActor(new RolePermissions);
    }

    private function company(CompanyId $companyId, bool $active = true): Company
    {
        return Company::open('Acme', 'acme-'.$companyId->value, null, 2, 'small_business', 10, 2, $active, 'UTC')
            ->withId($companyId->value);
    }

    private function adminCommand(CompanyId $companyId, UserRole $actor = UserRole::SuperAdmin): CreateCompanyAdminData
    {
        return new CreateCompanyAdminData(
            $actor,
            $companyId,
            'Ada',
            'Admin',
            'ada',
            'Ada@Acme.test',
            '5551112233',
            'plain-secret',
        );
    }

    private function user(
        CompanyId $companyId,
        string $firstName,
        string $lastName,
        string $nickname,
        string $email,
        UserRole $role,
    ): PlatformUser {
        return PlatformUser::register(
            $companyId,
            $firstName,
            $lastName,
            $nickname,
            $email,
            null,
            $role,
            true,
            'secret',
        );
    }
}

final class MemoryUsers implements UserRepository
{
    /** @var array<int, PlatformUser> */
    public array $saved = [];

    public function findByEmail(string $email): ?PlatformUser
    {
        foreach ($this->saved as $user) {
            if ($user->email() === $email) {
                return $user;
            }
        }

        return null;
    }

    public function findByNickname(string $nickname): ?PlatformUser
    {
        foreach ($this->saved as $user) {
            if ($user->nickname() === $nickname) {
                return $user;
            }
        }

        return null;
    }

    public function find(int $userId): ?PlatformUser
    {
        return $this->saved[$userId] ?? null;
    }

    public function save(PlatformUser $user): PlatformUser
    {
        try {
            $id = $user->id();
        } catch (UserIsNotPersisted) {
            $id = count($this->saved) + 1;
            $user = $user->withId($id);
        }

        $this->saved[$id] = $user;

        return $user;
    }
}

final class MemoryCompanies implements CompanyRepository
{
    /** @param  array<int, Company>  $companies */
    public function __construct(private array $companies) {}

    public function find(CompanyId $companyId): ?Company
    {
        return $this->companies[$companyId->value] ?? null;
    }

    public function slugExists(string $slug): bool
    {
        foreach ($this->companies as $company) {
            if ($company->slug() === $slug) {
                return true;
            }
        }

        return false;
    }

    public function save(Company $company): Company
    {
        $this->companies[$company->id()->value] = $company;

        return $company;
    }
}
