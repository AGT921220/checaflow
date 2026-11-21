<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Features\Attendance\Application\GetTodayEmployeeStatus;
use App\Features\Attendance\Application\LoadEmployeeShift;
use App\Features\Attendance\Application\RegisterPunch;
use App\Features\Attendance\Domain\AttendanceLog;
use App\Features\Attendance\Domain\CompanyClock;
use App\Features\Attendance\Domain\DailyAttendanceRepository;
use App\Features\Attendance\Domain\PunchSource;
use App\Features\Attendance\Domain\PunchType;
use App\Features\Attendance\Domain\RegisterPunchData;
use App\Features\Attendance\Domain\WorkDate;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Exceptions\CompanyNotFound;
use App\Features\Users\Domain\UserRole;
use App\Http\Attendance\AttendanceLabels;
use App\Http\Auth\CurrentCompanyId;
use App\Http\Auth\CurrentEmployeeProfile;
use App\Http\Requests\StorePunchRequest;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TodayAttendanceController extends Controller
{
    public function index(
        Request $request,
        GetTodayEmployeeStatus $today,
        LoadEmployeeShift $shift,
        DailyAttendanceRepository $summaries,
        CompanyClock $clock,
        CurrentCompanyId $company,
        CurrentEmployeeProfile $employee,
        AttendanceLabels $labels,
    ): View {
        $companyId = $company->from($request);
        $employeeId = $employee->id($request);
        $zone = $this->zone($clock, $companyId);
        $status = $today->handle($companyId, $employeeId);
        $assignment = $shift->handle($companyId, $employeeId);
        $branch = $assignment->branch;
        $location = $branch->location();

        return view('attendance.today', [
            'title' => 'Asistencia',
            'timezone' => $zone->getName(),
            'clock' => $clock->now()->setTimezone($zone)->format('H:i:s'),
            'statusLabel' => $labels->status($status->status->value),
            'nextPunch' => $status->nextPunchType?->value,
            'actionLabel' => $labels->action($status->nextPunchType?->value),
            'lateMinutes' => $status->lateMinutes,
            'workedMinutes' => $status->workedMinutes,
            'punches' => array_map(fn (AttendanceLog $punch): array => [
                'label' => $labels->punch($punch->punchType()->value),
                'time' => $punch->punchedAt()->setTimezone($zone)->format('H:i'),
            ], $status->punches),
            'history' => $this->history($summaries, $labels, $companyId, $employeeId, $zone),
            'mapsApiKey' => (string) config('services.google.maps_key', ''),
            'branchName' => $branch->name(),
            'branchAddress' => $branch->address(),
            'requireGeofence' => $branch->requiresGeofence(),
            'branchLatitude' => $location?->latitude,
            'branchLongitude' => $location?->longitude,
            'branchRadius' => $branch->geofenceRadiusMeters(),
        ]);
    }

    public function store(
        StorePunchRequest $request,
        RegisterPunch $register,
        CurrentCompanyId $company,
        CurrentEmployeeProfile $employee,
    ): RedirectResponse {
        $companyId = $company->from($request);
        $role = $request->user()?->role;

        if (! is_string($role)) {
            abort(403);
        }

        $register->handle(new RegisterPunchData(
            $companyId,
            $employee->id($request),
            PunchType::from($request->string('punch_type')->toString()),
            PunchSource::MobileWeb,
            UserRole::from($role),
            $companyId,
            $request->location(),
            $request->ip(),
        ));

        return redirect()->route('attendance.today')->with('status', 'Marca registrada.');
    }

    /**
     * @return list<array{date: string, status: string, clockIn: ?string, clockOut: ?string}>
     */
    private function history(
        DailyAttendanceRepository $summaries,
        AttendanceLabels $labels,
        CompanyId $companyId,
        int $employeeId,
        DateTimeZone $zone,
    ): array {
        $rows = [];
        $cursor = new DateTimeImmutable('now', $zone);

        foreach (range(0, 6) as $daysAgo) {
            $date = new WorkDate($cursor->modify("-{$daysAgo} days")->format('Y-m-d'));
            $summary = $summaries->find($companyId, $employeeId, $date);

            if ($summary === null) {
                continue;
            }

            $rows[] = [
                'date' => $summary->workDate()->value,
                'status' => $labels->status($summary->status()->value),
                'clockIn' => $this->time($summary->clockInAt(), $zone),
                'clockOut' => $this->time($summary->clockOutAt(), $zone),
            ];
        }

        return $rows;
    }

    private function zone(CompanyClock $clock, CompanyId $companyId): DateTimeZone
    {
        $name = $clock->timezone($companyId);

        if ($name === null) {
            throw new CompanyNotFound;
        }

        try {
            return new DateTimeZone($name);
        } catch (Exception) {
            throw new CompanyNotFound;
        }
    }

    private function time(?DateTimeImmutable $instant, DateTimeZone $zone): ?string
    {
        if ($instant === null) {
            return null;
        }

        return $instant->setTimezone($zone)->format('H:i');
    }
}
