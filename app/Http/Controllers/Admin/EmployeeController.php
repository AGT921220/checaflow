<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Features\Branches\Application\SearchBranches;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Employees\Application\AssignEmployeeSchedule;
use App\Features\Employees\Application\OpenEmployeeAccount;
use App\Features\Employees\Application\SearchEmployees;
use App\Features\Employees\Application\UpdateEmployee;
use App\Features\Employees\Domain\AssignEmployeeScheduleData;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\OpenEmployeeAccountData;
use App\Features\Employees\Domain\SearchEmployeesCriteria;
use App\Features\Employees\Domain\UpdateEmployeeData;
use App\Features\Schedules\Application\SearchSchedules;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\UserRole;
use App\Http\Auth\CurrentCompanyId;
use App\Http\Auth\TenantCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Search\SearchListInput;
use App\Http\Requests\AssignEmployeeScheduleRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Company;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class EmployeeController extends Controller
{
    public function page(
        Request $request,
        SearchBranches $searchBranches,
        SearchSchedules $searchSchedules,
        TenantCompanyScope $scope,
        CurrentCompanyId $company,
    ): View {
        $isPlatform = $scope->isPlatform($request);
        $companyId = $isPlatform ? null : $company->from($request);
        $branches = $companyId === null
            ? []
            : $searchBranches->handle($this->lookupBranches($companyId))->items;
        $schedules = $companyId === null
            ? []
            : $searchSchedules->handle($this->lookupSchedules($companyId))->items;

        return view('admin.employees.index', [
            'title' => 'Empleados',
            'isPlatform' => $isPlatform,
            'canManage' => ! $isPlatform,
            'openModal' => old('_form'),
            'companies' => $isPlatform ? $this->companies() : [],
            'branches' => $branches,
            'schedules' => $schedules,
            'bootstrap' => [
                'listUrl' => route('admin.employees.index'),
                'isPlatform' => $isPlatform,
                'canManage' => ! $isPlatform,
                'companies' => $isPlatform ? $this->companies() : [],
            ],
        ]);
    }

    public function index(
        Request $request,
        SearchEmployees $searchEmployees,
        SearchBranches $searchBranches,
        SearchSchedules $searchSchedules,
        TenantCompanyScope $scope,
        SearchListInput $list,
    ): JsonResponse {
        $companyId = $scope->optional($request);
        $paging = $list->paging('employees');
        $result = $searchEmployees->handle(new SearchEmployeesCriteria(
            companyId: $companyId,
            branchId: $list->optionalInt('employees', 'branch_id'),
            scheduleId: $list->optionalInt('employees', 'schedule_id'),
            isActive: $list->optionalBool('employees', 'is_active'),
            employeeCode: $list->optionalString('employees', 'employee_code'),
            sortColumn: $paging['sortColumn'],
            sortDirection: $paging['sortDirection'],
            page: $paging['page'],
            perPage: $paging['perPage'],
            allowedSortColumns: $paging['allowedSortColumns'],
            maxPerPage: $paging['maxPerPage'],
        ));

        /** @var list<Employee> $employees */
        $employees = $result->items;
        $accounts = $this->accounts($employees);
        $companyNames = $this->companyNames($employees);
        $branchNames = $this->namedMap($searchBranches->handle($this->lookupBranches($companyId))->items);
        $scheduleNames = $this->namedMap($searchSchedules->handle($this->lookupSchedules($companyId))->items);

        $rows = [];

        foreach ($employees as $employee) {
            $account = $accounts[$employee->userId()] ?? null;
            $rows[] = [
                'id' => $employee->id(),
                'companyId' => $employee->companyId()->value,
                'companyName' => $companyNames[$employee->companyId()->value] ?? '—',
                'userId' => $employee->userId(),
                'name' => $account['name'] ?? $employee->employeeCode(),
                'nickname' => $account['nickname'] ?? '—',
                'email' => $account['email'] ?? null,
                'phone' => $account['phone'] ?? null,
                'photoUrl' => $this->photoUrl($account['photoPath'] ?? null),
                'employeeCode' => $employee->employeeCode(),
                'positionTitle' => $employee->positionTitle(),
                'hireDate' => $employee->hireDate()?->format('Y-m-d'),
                'branchId' => $employee->branchId(),
                'branchName' => $branchNames[$employee->branchId()] ?? '—',
                'scheduleId' => $employee->scheduleId(),
                'scheduleName' => $scheduleNames[$employee->scheduleId()] ?? '—',
                'isActive' => $employee->isActive(),
                'updateUrl' => route('admin.employees.update', $employee->id()),
            ];
        }

        return response()->json([
            'data' => $rows,
            'meta' => $result->meta(),
            'filters' => [
                'branches' => $this->options($searchBranches->handle($this->lookupBranches($companyId))->items),
                'schedules' => $this->options($searchSchedules->handle($this->lookupSchedules($companyId))->items),
            ],
        ]);
    }

    private function lookupBranches(?CompanyId $companyId): SearchBranchesCriteria
    {
        $max = (int) config('branches.max_per_page', 100);

        return new SearchBranchesCriteria(
            companyId: $companyId,
            isActive: true,
            page: 1,
            perPage: $max,
            allowedSortColumns: array_values(config('branches.sortable', ['name', 'is_active'])),
            maxPerPage: $max,
        );
    }

    private function lookupSchedules(?CompanyId $companyId): SearchSchedulesCriteria
    {
        $max = (int) config('schedules.max_per_page', 100);

        return new SearchSchedulesCriteria(
            companyId: $companyId,
            isActive: true,
            page: 1,
            perPage: $max,
            allowedSortColumns: array_values(config('schedules.sortable', ['name', 'is_active'])),
            maxPerPage: $max,
        );
    }

    public function store(
        StoreEmployeeRequest $request,
        OpenEmployeeAccount $openAccount,
        TenantCompanyScope $scope,
    ): RedirectResponse {
        $companyId = $scope->forMutation($request);
        $hireDate = null;

        if ($request->filled('hire_date')) {
            $hireDate = new DateTimeImmutable($request->string('hire_date')->toString());
        }

        DB::transaction(function () use ($request, $openAccount, $companyId, $hireDate): void {
            $role = $request->user()?->role;

            if (! is_string($role)) {
                abort(403);
            }

            $openAccount->handle(new OpenEmployeeAccountData(
                UserRole::from($role),
                $companyId,
                $companyId,
                $request->string('name')->toString(),
                $request->string('nickname')->toString(),
                $request->filled('email') ? $request->string('email')->toString() : null,
                $request->filled('phone') ? $request->string('phone')->toString() : null,
                $request->string('password')->toString(),
                $request->integer('branch_id'),
                $request->integer('schedule_id'),
                $request->string('employee_code')->toString(),
                $request->filled('position_title') ? $request->string('position_title')->toString() : null,
                $hireDate,
            ));
        });

        return redirect()->route('employees.page')->with('status', 'Empleado creado.');
    }

    public function update(
        UpdateEmployeeRequest $request,
        int $employeeProfile,
        UpdateEmployee $update,
        TenantCompanyScope $scope,
    ): RedirectResponse {
        $companyId = $scope->forMutation($request);
        $hireDate = null;

        if ($request->filled('hire_date')) {
            $hireDate = new DateTimeImmutable($request->string('hire_date')->toString());
        }

        $photo = $request->file('photo');
        $binary = null;
        $extension = null;

        if ($photo instanceof UploadedFile) {
            $contents = file_get_contents($photo->getRealPath());

            if ($contents !== false) {
                $binary = $contents;
                $extension = $photo->extension() ?: 'jpg';
            }
        }

        $role = $request->user()?->role;

        if (! is_string($role)) {
            abort(403);
        }

        $update->handle(new UpdateEmployeeData(
            UserRole::from($role),
            $companyId,
            $companyId,
            $employeeProfile,
            $request->string('name')->toString(),
            $request->string('nickname')->toString(),
            $request->filled('email') ? $request->string('email')->toString() : null,
            $request->filled('phone') ? $request->string('phone')->toString() : null,
            $request->integer('branch_id'),
            $request->integer('schedule_id'),
            $request->string('employee_code')->toString(),
            $request->filled('position_title') ? $request->string('position_title')->toString() : null,
            $hireDate,
            $request->boolean('is_active'),
            $binary,
            $extension,
        ));

        return redirect()->route('employees.page')->with('status', 'Empleado actualizado.');
    }

    public function assignSchedule(
        AssignEmployeeScheduleRequest $request,
        int $employeeProfile,
        AssignEmployeeSchedule $assign,
        TenantCompanyScope $scope,
    ): RedirectResponse {
        $companyId = $scope->forMutation($request);
        $role = $request->user()?->role;

        if (! is_string($role)) {
            abort(403);
        }

        $assign->handle(new AssignEmployeeScheduleData(
            UserRole::from($role),
            $companyId,
            $companyId,
            $employeeProfile,
            $request->integer('schedule_id'),
        ));

        return redirect()->route('employees.page')->with('status', 'Horario actualizado.');
    }

    /**
     * @param  list<Employee>  $employees
     * @return array<int, array{name: string, nickname: string, email: ?string, phone: ?string, photoPath: ?string}>
     */
    private function accounts(array $employees): array
    {
        $userIds = array_map(fn (Employee $employee): int => $employee->userId(), $employees);

        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'name', 'nickname', 'email', 'phone', 'photo_path'])
            ->mapWithKeys(fn (User $user): array => [
                (int) $user->id => [
                    'name' => (string) $user->name,
                    'nickname' => (string) ($user->nickname ?? ''),
                    'email' => $user->email !== null ? (string) $user->email : null,
                    'phone' => $user->phone !== null ? (string) $user->phone : null,
                    'photoPath' => $user->photo_path !== null ? (string) $user->photo_path : null,
                ],
            ])
            ->all();
    }

    /**
     * @param  list<Employee>  $employees
     * @return array<int, string>
     */
    private function companyNames(array $employees): array
    {
        $ids = array_values(array_unique(array_map(
            fn (Employee $employee): int => $employee->companyId()->value,
            $employees,
        )));

        if ($ids === []) {
            return [];
        }

        return Company::query()->whereIn('id', $ids)->pluck('name', 'id')->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function companies(): array
    {
        return Company::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Company $company): array => [
                'id' => (int) $company->id,
                'name' => (string) $company->name,
            ])
            ->all();
    }

    private function photoUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @param  list<object{id(): int, name(): string}>  $rows
     * @return array<int, string>
     */
    private function namedMap(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $map[$row->id()] = $row->name();
        }

        return $map;
    }

    /**
     * @param  list<object{id(): int, name(): string}>  $rows
     * @return list<array{id: int, name: string}>
     */
    private function options(array $rows): array
    {
        return array_map(
            fn (object $row): array => ['id' => $row->id(), 'name' => $row->name()],
            $rows,
        );
    }

}
