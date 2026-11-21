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
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignEmployeeScheduleRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class EmployeeController extends Controller
{
    public function index(
        Request $request,
        SearchEmployees $searchEmployees,
        SearchBranches $searchBranches,
        SearchSchedules $searchSchedules,
        CurrentCompanyId $company,
    ): View {
        $companyId = $company->from($request);
        $employees = $searchEmployees->handle(new SearchEmployeesCriteria($companyId));

        $branches = $searchBranches->handle(new SearchBranchesCriteria($companyId, true));
        $schedules = $searchSchedules->handle(new SearchSchedulesCriteria($companyId, true));

        return view('admin.employees.index', [
            'title' => 'Empleados',
            'employees' => $employees,
            'accounts' => $this->accounts($companyId, $employees),
            'branches' => $branches,
            'schedules' => $schedules,
            'branchNames' => $this->namedMap($branches),
            'scheduleNames' => $this->namedMap($schedules),
            'photoUrl' => fn (?string $path): ?string => $this->photoUrl($path),
            'openModal' => old('_form'),
        ]);
    }

    public function store(
        StoreEmployeeRequest $request,
        OpenEmployeeAccount $openAccount,
        CurrentCompanyId $company,
    ): RedirectResponse {
        $hireDate = null;

        if ($request->filled('hire_date')) {
            $hireDate = new DateTimeImmutable($request->string('hire_date')->toString());
        }

        DB::transaction(function () use ($request, $openAccount, $company, $hireDate): void {
            $companyId = $company->from($request);
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

        return redirect()->route('admin.employees.index')->with('status', 'Empleado creado.');
    }

    public function update(
        UpdateEmployeeRequest $request,
        int $employeeProfile,
        UpdateEmployee $update,
        CurrentCompanyId $company,
    ): RedirectResponse {
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

        $companyId = $company->from($request);
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

        return redirect()->route('admin.employees.index')->with('status', 'Empleado actualizado.');
    }

    public function assignSchedule(
        AssignEmployeeScheduleRequest $request,
        int $employeeProfile,
        AssignEmployeeSchedule $assign,
        CurrentCompanyId $company,
    ): RedirectResponse {
        $companyId = $company->from($request);
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

        return redirect()->route('admin.employees.index')->with('status', 'Horario actualizado.');
    }

    /**
     * @param  list<Employee>  $employees
     * @return array<int, array{name: string, nickname: string, email: ?string, phone: ?string, photoPath: ?string}>
     */
    private function accounts(CompanyId $companyId, array $employees): array
    {
        $userIds = array_map(fn (Employee $employee): int => $employee->userId(), $employees);

        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->where('company_id', $companyId->value)
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
}
