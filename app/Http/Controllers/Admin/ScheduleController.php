<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Features\Schedules\Application\CreateSchedule;
use App\Features\Schedules\Application\SearchSchedules;
use App\Features\Schedules\Application\UpdateSchedule;
use App\Features\Schedules\Domain\CreateScheduleData;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Schedules\Domain\UpdateScheduleData;
use App\Features\Users\Domain\UserRole;
use App\Http\Auth\RequestPageQuery;
use App\Http\Auth\TenantCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ScheduleController extends Controller
{
    public function page(Request $request, TenantCompanyScope $scope): View
    {
        $isPlatform = $scope->isPlatform($request);

        return view('admin.schedules.index', [
            'title' => 'Horarios',
            'isPlatform' => $isPlatform,
            'canManage' => ! $isPlatform,
            'openModal' => old('_form'),
            'weekdays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'weekdayLabels' => [
                'monday' => 'Lunes',
                'tuesday' => 'Martes',
                'wednesday' => 'Miércoles',
                'thursday' => 'Jueves',
                'friday' => 'Viernes',
                'saturday' => 'Sábado',
                'sunday' => 'Domingo',
            ],
            'companies' => $isPlatform ? $this->companies() : [],
        ]);
    }

    public function index(
        Request $request,
        SearchSchedules $search,
        TenantCompanyScope $scope,
        RequestPageQuery $pages,
    ): JsonResponse {
        $companyId = $scope->optional($request);
        $result = $search->page(new SearchSchedulesCriteria($companyId), $pages->from($request));
        /** @var list<Schedule> $schedules */
        $schedules = $result->items;
        $companyNames = $this->companyNames($schedules);
        $labels = [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miércoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
        ];
        $canManage = ! $scope->isPlatform($request);

        $rows = [];

        foreach ($schedules as $schedule) {
            $clockIn = sprintf('%02d:%02d', $schedule->clockIn()->hour(), $schedule->clockIn()->minute());
            $clockOut = sprintf('%02d:%02d', $schedule->clockOut()->hour(), $schedule->clockOut()->minute());
            $days = $schedule->workingDays()->values();
            $rows[] = [
                'id' => $schedule->id(),
                'companyId' => $schedule->companyId()->value,
                'companyName' => $companyNames[$schedule->companyId()->value] ?? '—',
                'name' => $schedule->name(),
                'clockIn' => $clockIn,
                'clockOut' => $clockOut,
                'lateToleranceMinutes' => $schedule->lateToleranceMinutes(),
                'allowedLunchMinutes' => $schedule->allowedLunchMinutes(),
                'workingDays' => $days,
                'workingDaysLabel' => implode(', ', array_map(fn (string $day): string => $labels[$day] ?? $day, $days)),
                'isActive' => $schedule->isActive(),
                'updateUrl' => $canManage ? route('admin.schedules.update', $schedule->id()) : null,
            ];
        }

        return response()->json([
            'data' => $rows,
            'meta' => $result->meta(),
        ]);
    }

    public function store(StoreScheduleRequest $request, CreateSchedule $create, TenantCompanyScope $scope): RedirectResponse
    {
        $days = $request->input('working_days', []);
        $companyId = $scope->forMutation($request);
        $role = $request->user()?->role;

        if (! is_string($role)) {
            abort(403);
        }

        $create->handle(new CreateScheduleData(
            UserRole::from($role),
            $companyId,
            $companyId,
            $request->string('name')->toString(),
            $request->string('clock_in_time')->toString(),
            $request->string('clock_out_time')->toString(),
            $request->integer('late_tolerance_minutes'),
            $request->integer('allowed_lunch_minutes'),
            is_array($days) ? array_values($days) : [],
            $request->boolean('is_active'),
        ));

        return redirect()->route('schedules.page')->with('status', 'Horario creado.');
    }

    public function update(
        UpdateScheduleRequest $request,
        int $schedule,
        UpdateSchedule $update,
        TenantCompanyScope $scope,
    ): RedirectResponse {
        $days = $request->input('working_days', []);
        $companyId = $scope->forMutation($request);
        $role = $request->user()?->role;

        if (! is_string($role)) {
            abort(403);
        }

        $update->handle(new UpdateScheduleData(
            UserRole::from($role),
            $companyId,
            $companyId,
            $schedule,
            $request->string('name')->toString(),
            $request->string('clock_in_time')->toString(),
            $request->string('clock_out_time')->toString(),
            $request->integer('late_tolerance_minutes'),
            $request->integer('allowed_lunch_minutes'),
            is_array($days) ? array_values($days) : [],
            $request->boolean('is_active'),
        ));

        return redirect()->route('schedules.page')->with('status', 'Horario actualizado.');
    }

    /**
     * @param  list<Schedule>  $schedules
     * @return array<int, string>
     */
    private function companyNames(array $schedules): array
    {
        $ids = array_values(array_unique(array_map(
            fn (Schedule $schedule): int => $schedule->companyId()->value,
            $schedules,
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
}
