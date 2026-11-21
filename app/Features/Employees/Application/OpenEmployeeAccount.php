<?php

declare(strict_types=1);

namespace App\Features\Employees\Application;

use App\Features\Employees\Domain\CreateEmployeeData;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\EmployeeAccountRegistrar;
use App\Features\Employees\Domain\OpenEmployeeAccountData;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\CompanyActor;
use App\Features\Users\Domain\PermissionName;

final class OpenEmployeeAccount
{
    public function __construct(
        private EmployeeAccountRegistrar $accounts,
        private CreateEmployee $createEmployee,
        private AuthorizeActor $access,
    ) {}

    public function handle(OpenEmployeeAccountData $command): Employee
    {
        $this->access->handle(
            $command->actorRole,
            $command->actorCompanyId,
            PermissionName::EmployeesManage,
            $command->companyId,
        );
        CompanyActor::assertCanOperate($command->actorRole, $command->actorCompanyId, $command->companyId);

        $userId = $this->accounts->register(
            $command->companyId,
            $command->name,
            $command->nickname,
            $command->email,
            $command->phone,
            $command->password,
        );

        return $this->createEmployee->handle(new CreateEmployeeData(
            $command->actorRole,
            $command->actorCompanyId,
            $command->companyId,
            $userId,
            $command->branchId,
            $command->scheduleId,
            $command->employeeCode,
            $command->positionTitle,
            $command->hireDate,
        ));
    }
}
