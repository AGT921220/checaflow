<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

final class RolePermissions implements PermissionChecker
{
    public function allows(UserRole $role, PermissionName $permission): bool
    {
        return in_array($permission, $this->granted($role), true);
    }

    /**
     * @return list<PermissionName>
     */
    private function granted(UserRole $role): array
    {
        return match ($role) {
            UserRole::SuperAdmin => [
                PermissionName::CompaniesManage,
                PermissionName::CompanyAdminsManage,
            ],
            UserRole::CompanyAdmin => [
                PermissionName::BranchesManage,
                PermissionName::SchedulesManage,
                PermissionName::UsersManage,
                PermissionName::EmployeesManage,
                PermissionName::AttendanceViewCompanyReport,
                PermissionName::AttendanceAdjustLogs,
                PermissionName::ProfileUpdateOwnPhoto,
            ],
            UserRole::Hr => [
                PermissionName::EmployeesManage,
                PermissionName::SchedulesManage,
                PermissionName::AttendanceViewCompanyReport,
                PermissionName::ProfileUpdateOwnPhoto,
            ],
            UserRole::Employee => [
                PermissionName::AttendancePunch,
                PermissionName::AttendanceViewOwnHistory,
                PermissionName::ProfileUpdateOwnPhoto,
            ],
        };
    }
}
