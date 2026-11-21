<?php

declare(strict_types=1);

namespace App\Features\Schedules\Application;

use App\Features\Schedules\Domain\CreateScheduleData;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\CompanyActor;
use App\Features\Users\Domain\PermissionName;

final class CreateSchedule
{
    public function __construct(
        private ScheduleRepository $schedules,
        private AuthorizeActor $access,
    ) {}

    public function handle(CreateScheduleData $command): Schedule
    {
        $this->access->handle(
            $command->actorRole,
            $command->actorCompanyId,
            PermissionName::SchedulesManage,
            $command->companyId,
        );
        CompanyActor::assertCanOperate($command->actorRole, $command->actorCompanyId, $command->companyId);

        return $this->schedules->save(Schedule::open(
            $command->companyId,
            $command->name,
            $command->clockInTime,
            $command->clockOutTime,
            $command->lateToleranceMinutes,
            $command->allowedLunchMinutes,
            $command->workingDays,
            $command->isActive,
        ));
    }
}
