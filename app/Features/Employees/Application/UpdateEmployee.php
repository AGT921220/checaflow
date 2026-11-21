<?php

declare(strict_types=1);

namespace App\Features\Employees\Application;

use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\Exceptions\BranchDoesNotBelongToCompany;
use App\Features\Branches\Domain\Exceptions\BranchIsInactive;
use App\Features\Branches\Domain\Exceptions\BranchNotFound;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\Exceptions\EmployeeCodeAlreadyUsed;
use App\Features\Employees\Domain\Exceptions\EmployeeDoesNotBelongToCompany;
use App\Features\Employees\Domain\Exceptions\EmployeeNotFound;
use App\Features\Employees\Domain\UpdateEmployeeData;
use App\Features\Schedules\Domain\Exceptions\ScheduleDoesNotBelongToCompany;
use App\Features\Schedules\Domain\Exceptions\ScheduleIsInactive;
use App\Features\Schedules\Domain\Exceptions\ScheduleNotFound;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\CompanyActor;
use App\Features\Users\Domain\Exceptions\EmailAlreadyUsed;
use App\Features\Users\Domain\Exceptions\InvalidProfilePhoto;
use App\Features\Users\Domain\Exceptions\NicknameAlreadyUsed;
use App\Features\Users\Domain\Exceptions\UserNotFound;
use App\Features\Users\Domain\PermissionName;
use App\Features\Users\Domain\PlatformUser;
use App\Features\Users\Domain\ProfilePhotoStore;
use App\Features\Users\Domain\UserRepository;

final class UpdateEmployee
{
    private const ALLOWED_PHOTO = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        private EmployeeRepository $employees,
        private UserRepository $users,
        private BranchRepository $branches,
        private ScheduleRepository $schedules,
        private ProfilePhotoStore $photos,
        private AuthorizeActor $access,
    ) {}

    public function handle(UpdateEmployeeData $command): Employee
    {
        $this->access->handle(
            $command->actorRole,
            $command->actorCompanyId,
            PermissionName::EmployeesManage,
            $command->companyId,
        );
        CompanyActor::assertCanOperate($command->actorRole, $command->actorCompanyId, $command->companyId);

        $employee = $this->employee($command);
        $this->assertBranch($command);
        $this->assertSchedule($command);
        $this->assertCodeIsFree($command, $employee);
        $user = $this->user($employee);
        $this->assertUniqueContact($command, $user);

        $contact = $user->withContact(
            $command->name,
            $user->firstName(),
            $user->lastName(),
            $command->nickname,
            $command->email,
            $command->phone,
        );
        $contact = $this->withOptionalPhoto($contact, $command);
        $this->users->save($contact);

        return $this->employees->save($employee->revise(
            $command->branchId,
            $command->scheduleId,
            $command->employeeCode,
            $command->positionTitle,
            $command->hireDate,
            $command->isActive,
        ));
    }

    private function employee(UpdateEmployeeData $command): Employee
    {
        $employee = $this->employees->find($command->companyId, $command->employeeProfileId);

        if ($employee === null) {
            throw new EmployeeNotFound;
        }

        if (! $employee->belongsTo($command->companyId)) {
            throw new EmployeeDoesNotBelongToCompany;
        }

        return $employee;
    }

    private function user(Employee $employee): PlatformUser
    {
        $user = $this->users->find($employee->userId());

        if ($user === null) {
            throw new UserNotFound;
        }

        return $user;
    }

    private function assertBranch(UpdateEmployeeData $command): void
    {
        $branch = $this->branches->find($command->companyId, $command->branchId);

        if ($branch === null) {
            throw new BranchNotFound;
        }

        if (! $branch->belongsTo($command->companyId)) {
            throw new BranchDoesNotBelongToCompany;
        }

        if (! $branch->isActive()) {
            throw new BranchIsInactive;
        }
    }

    private function assertSchedule(UpdateEmployeeData $command): void
    {
        $schedule = $this->schedules->find($command->companyId, $command->scheduleId);

        if ($schedule === null) {
            throw new ScheduleNotFound;
        }

        if (! $schedule->belongsTo($command->companyId)) {
            throw new ScheduleDoesNotBelongToCompany;
        }

        if (! $schedule->isActive()) {
            throw new ScheduleIsInactive;
        }
    }

    private function assertCodeIsFree(UpdateEmployeeData $command, Employee $employee): void
    {
        $code = trim($command->employeeCode);

        if ($employee->employeeCode() === $code) {
            return;
        }

        if ($this->employees->codeExists($command->companyId, $code)) {
            throw new EmployeeCodeAlreadyUsed;
        }
    }

    private function assertUniqueContact(UpdateEmployeeData $command, PlatformUser $user): void
    {
        $nickname = mb_strtolower(trim($command->nickname));

        if ($user->nickname() !== $nickname) {
            $taken = $this->users->findByNickname($nickname);

            if ($taken !== null && $taken->id() !== $user->id()) {
                throw new NicknameAlreadyUsed;
            }
        }

        if ($command->email === null || $command->email === '') {
            return;
        }

        $email = mb_strtolower(trim($command->email));

        if ($user->email() === $email) {
            return;
        }

        $takenEmail = $this->users->findByEmail($email);

        if ($takenEmail !== null && $takenEmail->id() !== $user->id()) {
            throw new EmailAlreadyUsed;
        }
    }

    private function withOptionalPhoto(PlatformUser $user, UpdateEmployeeData $command): PlatformUser
    {
        if ($command->photoBinary === null || $command->photoExtension === null) {
            return $user;
        }

        $extension = mb_strtolower(trim($command->photoExtension));

        if (! in_array($extension, self::ALLOWED_PHOTO, true)) {
            throw new InvalidProfilePhoto('Usa una imagen JPG, PNG o WEBP.');
        }

        if ($command->photoBinary === '' || strlen($command->photoBinary) > 2_000_000) {
            throw new InvalidProfilePhoto('La foto debe pesar menos de 2 MB.');
        }

        $path = $this->photos->put($user->id(), $command->photoBinary, $extension);
        $previous = $user->photoPath();
        $updated = $user->withPhotoPath($path);
        $this->photos->delete($previous);

        return $updated;
    }
}
