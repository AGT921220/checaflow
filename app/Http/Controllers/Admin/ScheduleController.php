<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Features\Schedules\Application\CreateSchedule;
use App\Features\Schedules\Application\SearchSchedules;
use App\Features\Schedules\Application\UpdateSchedule;
use App\Features\Schedules\Domain\CreateScheduleData;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Schedules\Domain\UpdateScheduleData;
use App\Features\Users\Domain\UserRole;
use App\Http\Auth\CurrentCompanyId;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ScheduleController extends Controller
{
    public function index(Request $request, SearchSchedules $search, CurrentCompanyId $company): View
    {
        return view('admin.schedules.index', [
            'title' => 'Horarios',
            'schedules' => $search->handle(new SearchSchedulesCriteria($company->from($request))),
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
            'openModal' => old('_form'),
        ]);
    }

    public function store(StoreScheduleRequest $request, CreateSchedule $create, CurrentCompanyId $company): RedirectResponse
    {
        $days = $request->input('working_days', []);

        $companyId = $company->from($request);
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

        return redirect()->route('admin.schedules.index')->with('status', 'Horario creado.');
    }

    public function update(
        UpdateScheduleRequest $request,
        int $schedule,
        UpdateSchedule $update,
        CurrentCompanyId $company,
    ): RedirectResponse {
        $days = $request->input('working_days', []);

        $companyId = $company->from($request);
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

        return redirect()->route('admin.schedules.index')->with('status', 'Horario actualizado.');
    }
}
