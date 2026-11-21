<?php

declare(strict_types=1);

namespace App\Features\Employees\Infrastructure;

use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\Exceptions\EmployeeIsNotPersisted;
use App\Features\Employees\Domain\Exceptions\EmployeeNotFound;
use App\Features\Employees\Domain\SearchEmployeesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\PagedResult;
use App\Models\EmployeeProfile;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

final class EloquentEmployeeRepository implements EmployeeRepository
{
    public function save(Employee $employee): Employee
    {
        $record = $this->record($employee);
        $record->company_id = $employee->companyId()->value;
        $record->user_id = $employee->userId();
        $record->branch_id = $employee->branchId();
        $record->schedule_id = $employee->scheduleId();
        $record->employee_code = $employee->employeeCode();
        $record->position_title = $employee->positionTitle();
        $record->hire_date = $employee->hireDate()?->format('Y-m-d');
        $record->is_active = $employee->isActive();
        $record->save();

        return $this->map($record);
    }

    public function find(CompanyId $companyId, int $employeeProfileId): ?Employee
    {
        $record = EmployeeProfile::query()
            ->where('company_id', $companyId->value)
            ->find($employeeProfileId);

        if ($record === null) {
            return null;
        }

        return $this->map($record);
    }

    public function search(SearchEmployeesCriteria $criteria): PagedResult
    {
        $query = $this->filtered($criteria)->orderBy($criteria->sort->column, $criteria->sort->direction);
        $total = (clone $query)->count();
        $items = $query
            ->offset($criteria->offset())
            ->limit($criteria->perPage)
            ->get()
            ->map(fn (EmployeeProfile $record): Employee => $this->map($record))
            ->all();

        return new PagedResult(array_values($items), $total, $criteria->page, $criteria->perPage);
    }

    /**
     * @return Builder<EmployeeProfile>
     */
    private function filtered(SearchEmployeesCriteria $criteria): Builder
    {
        $query = EmployeeProfile::query();

        if ($criteria->companyId !== null) {
            $query->where('company_id', $criteria->companyId->value);
        }

        if ($criteria->branchId !== null) {
            $query->where('branch_id', $criteria->branchId);
        }

        if ($criteria->scheduleId !== null) {
            $query->where('schedule_id', $criteria->scheduleId);
        }

        if ($criteria->isActive !== null) {
            $query->where('is_active', $criteria->isActive);
        }

        if ($criteria->employeeCode !== null && $criteria->employeeCode !== '') {
            $query->where('employee_code', $criteria->employeeCode);
        }

        return $query;
    }

    public function count(CompanyId $companyId): int
    {
        return EmployeeProfile::query()->where('company_id', $companyId->value)->count();
    }

    public function codeExists(CompanyId $companyId, string $employeeCode): bool
    {
        return EmployeeProfile::withTrashed()
            ->where('company_id', $companyId->value)
            ->where('employee_code', $employeeCode)
            ->exists();
    }

    public function userAlreadyHasProfile(int $userId): bool
    {
        return EmployeeProfile::withTrashed()->where('user_id', $userId)->exists();
    }

    private function record(Employee $employee): EmployeeProfile
    {
        $id = $this->persistedId($employee);

        if ($id === null) {
            return new EmployeeProfile;
        }

        $record = EmployeeProfile::query()
            ->where('company_id', $employee->companyId()->value)
            ->find($id);

        if ($record === null) {
            throw new EmployeeNotFound;
        }

        return $record;
    }

    private function persistedId(Employee $employee): ?int
    {
        try {
            return $employee->id();
        } catch (EmployeeIsNotPersisted) {
            return null;
        }
    }

    private function map(EmployeeProfile $record): Employee
    {
        return new Employee(
            (int) $record->id,
            new CompanyId((int) $record->company_id),
            (int) $record->user_id,
            (int) $record->branch_id,
            (int) $record->schedule_id,
            $record->employee_code,
            $record->position_title,
            $this->date($record->hire_date),
            (bool) $record->is_active,
        );
    }

    private function date(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return new DateTimeImmutable($value->format('Y-m-d'));
        }

        return new DateTimeImmutable((string) $value);
    }
}
