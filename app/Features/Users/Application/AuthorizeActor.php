<?php

declare(strict_types=1);

namespace App\Features\Users\Application;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\CompanyActor;
use App\Features\Users\Domain\Exceptions\ActorCompanyMismatch;
use App\Features\Users\Domain\Exceptions\PermissionDenied;
use App\Features\Users\Domain\Exceptions\PersonalAccessDenied;
use App\Features\Users\Domain\PermissionChecker;
use App\Features\Users\Domain\PermissionName;
use App\Features\Users\Domain\UserRole;

final class AuthorizeActor
{
    public function __construct(private PermissionChecker $permissions) {}

    public function handle(
        UserRole $actorRole,
        ?CompanyId $actorCompanyId,
        PermissionName $permission,
        ?CompanyId $targetCompanyId = null,
        ?int $actorEmployeeProfileId = null,
        ?int $targetEmployeeProfileId = null,
    ): void {
        if (! $this->permissions->allows($actorRole, $permission)) {
            throw new PermissionDenied;
        }

        if ($actorRole === UserRole::SuperAdmin) {
            if ($permission->isTenant() && $permission !== PermissionName::AttendanceViewCompanyReport) {
                throw new PermissionDenied;
            }

            return;
        }

        $actor = new CompanyActor($actorRole, $actorCompanyId, $actorEmployeeProfileId);

        if ($permission->isTenant()) {
            $this->assertTenantCompany($actor, $targetCompanyId);
        }

        $this->assertOwnEmployee($actor, $permission, $targetEmployeeProfileId);
    }

    private function assertTenantCompany(CompanyActor $actor, ?CompanyId $targetCompanyId): void
    {
        if ($targetCompanyId === null) {
            throw new ActorCompanyMismatch;
        }

        $actor->assertCompany($targetCompanyId);
    }

    private function assertOwnEmployee(CompanyActor $actor, PermissionName $permission, ?int $targetEmployeeProfileId): void
    {
        if (! $permission->isPersonal()) {
            return;
        }

        if ($permission === PermissionName::ProfileUpdateOwnPhoto) {
            return;
        }

        if ($actor->actorRole !== UserRole::Employee) {
            return;
        }

        if ($actor->employeeProfileId === null || $actor->employeeProfileId !== $targetEmployeeProfileId) {
            throw new PersonalAccessDenied;
        }
    }
}
