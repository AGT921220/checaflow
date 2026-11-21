<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

enum PermissionName: string
{
    case CompaniesManage = 'companies.manage';
    case CompanyAdminsManage = 'company_admins.manage';
    case BranchesManage = 'branches.manage';
    case SchedulesManage = 'schedules.manage';
    case UsersManage = 'users.manage';
    case EmployeesManage = 'employees.manage';
    case AttendanceViewCompanyReport = 'attendance.view_company_report';
    case AttendanceAdjustLogs = 'attendance.adjust_logs';
    case AttendancePunch = 'attendance.punch';
    case AttendanceViewOwnHistory = 'attendance.view_own_history';
    case ProfileUpdateOwnPhoto = 'profile.update_own_photo';

    public function isTenant(): bool
    {
        return $this !== self::CompaniesManage && $this !== self::CompanyAdminsManage;
    }

    public function isPersonal(): bool
    {
        return $this === self::AttendancePunch
            || $this === self::AttendanceViewOwnHistory
            || $this === self::ProfileUpdateOwnPhoto;
    }
}
