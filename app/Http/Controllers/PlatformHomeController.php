<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Features\Users\Application\OpenCompanyAccount;
use App\Features\Users\Domain\OpenCompanyAccountData;
use App\Features\Users\Domain\PlanRepository;
use App\Features\Users\Domain\UserRole;
use App\Http\Requests\StoreCompanyRequest;
use App\Models\AttendanceLog;
use App\Models\Company;
use App\Models\EmployeeProfile;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PlatformHomeController extends Controller
{
    public function store(
        StoreCompanyRequest $request,
        OpenCompanyAccount $openAccount,
    ): RedirectResponse {
        $role = $request->user()?->role;

        if (! is_string($role)) {
            abort(403);
        }

        DB::transaction(function () use ($request, $openAccount, $role): void {
            $openAccount->handle(new OpenCompanyAccountData(
                UserRole::from($role),
                $request->string('company_name')->toString(),
                $request->integer('plan_id'),
                $request->integer('max_employees'),
                $request->string('first_name')->toString(),
                $request->string('last_name')->toString(),
                $request->string('nickname')->toString(),
                $request->filled('email') ? $request->string('email')->toString() : null,
                $request->filled('phone') ? $request->string('phone')->toString() : null,
                $request->string('password')->toString(),
            ));
        });

        return redirect()->route('platform.home')->with('status', 'Empresa y administrador creados.');
    }

    public function index(Request $request, PlanRepository $plans): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $companies = Company::query()
            ->with('plan')
            ->orderBy('name')
            ->get();

        $activeEmployees = EmployeeProfile::query()->where('is_active', true)->count();
        $punchCount = AttendanceLog::query()->count();
        $companiesByPlan = Plan::query()
            ->orderBy('min_employees')
            ->get()
            ->map(fn (Plan $plan): array => [
                'segment' => $plan->segment,
                'count' => $companies->where('plan_id', $plan->id)->count(),
            ])
            ->all();

        $employeeCounts = EmployeeProfile::query()
            ->selectRaw('company_id, COUNT(*) as total')
            ->where('is_active', true)
            ->groupBy('company_id')
            ->pluck('total', 'company_id');

        $punchCounts = AttendanceLog::query()
            ->selectRaw('company_id, COUNT(*) as total, MAX(punched_at) as last_punch')
            ->groupBy('company_id')
            ->get()
            ->keyBy('company_id');

        $rows = $companies->map(function (Company $company) use ($employeeCounts, $punchCounts): array {
            $punches = $punchCounts->get($company->id);

            return [
                'name' => $company->name,
                'plan' => $company->plan?->segment ?? $company->plan_code,
                'employees' => (int) ($employeeCounts[$company->id] ?? 0),
                'maxEmployees' => (int) $company->max_employees,
                'punches' => (int) ($punches?->total ?? 0),
                'lastPunch' => $punches?->last_punch,
            ];
        })->all();

        return view('platform.home', [
            'title' => 'Plataforma',
            'accountName' => $user->name,
            'accountHandle' => $user->nickname ?? $user->email,
            'companyCount' => $companies->count(),
            'activeEmployees' => $activeEmployees,
            'punchCount' => $punchCount,
            'companiesByPlan' => $companiesByPlan,
            'rows' => $rows,
            'plans' => $plans->all(),
        ]);
    }
}
