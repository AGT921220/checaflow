<?php

declare(strict_types=1);

namespace App\Features\Schedules\Application;

use App\Features\Schedules\Domain\Exceptions\ScheduleDoesNotBelongToCompany;
use App\Features\Schedules\Domain\Exceptions\ScheduleNotFound;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Schedules\Domain\UpdateScheduleData;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\CompanyActor;
use App\Features\Users\Domain\PermissionName;

final class UpdateSchedule
{
    public function __construct(
        private ScheduleRepository $schedules,
        private AuthorizeActor $access,
    ) {}

    public function handle(UpdateScheduleData $command): Schedule
    {
        $this->access->handle(
            $command->actorRole,
            $command->actorCompanyId,
            PermissionName::SchedulesManage,
            $command->companyId,
        );
        CompanyActor::assertCanOperate($command->actorRole, $command->actorCompanyId, $command->companyId);

        $schedule = $this->schedule($command);

        return $this->schedules->save($schedule->revise(
            $command->name,
            $command->clockInTime,
            $command->clockOutTime,
            $command->lateToleranceMinutes,
            $command->allowedLunchMinutes,
            $command->workingDays,
            $command->isActive,
        ));
    }

    private function schedule(UpdateScheduleData $command): Schedule
    {
        $schedule = $this->schedules->find($command->companyId, $command->scheduleId);

        if ($schedule === null) {
            throw new ScheduleNotFound;
        }

        if (! $schedule->belongsTo($command->companyId)) {
            throw new ScheduleDoesNotBelongToCompany;
        }

        return $schedule;
    }
}
