<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Schedules;

use App\Features\Schedules\Application\CreateSchedule;
use App\Features\Schedules\Domain\CreateScheduleData;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\Exceptions\ActorCompanyMismatch;
use App\Features\Users\Domain\Exceptions\PermissionDenied;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UserRole;
use PHPUnit\Framework\TestCase;

final class CreateScheduleTest extends TestCase
{
    public function test_company_admin_opens_a_schedule_in_their_company(): void
    {
        $companyId = new CompanyId(4);
        $action = new CreateSchedule(new MemoryScheduleRepository, $this->access());

        $schedule = $action->handle(new CreateScheduleData(
            UserRole::CompanyAdmin,
            $companyId,
            $companyId,
            'Office',
            '09:00',
            '18:00',
            10,
            60,
            ['monday'],
        ));

        $this->assertSame('Office', $schedule->name());
        $this->assertTrue($schedule->belongsTo($companyId));
    }

    public function test_it_rejects_another_company(): void
    {
        $action = new CreateSchedule(new MemoryScheduleRepository, $this->access());

        $this->expectException(ActorCompanyMismatch::class);

        $action->handle(new CreateScheduleData(
            UserRole::Hr,
            new CompanyId(4),
            new CompanyId(9),
            'Office',
            '09:00',
            '18:00',
            10,
            60,
            ['monday'],
        ));
    }

    public function test_a_super_admin_cannot_open_a_schedule(): void
    {
        $companyId = new CompanyId(4);
        $action = new CreateSchedule(new MemoryScheduleRepository, $this->access());

        $this->expectException(PermissionDenied::class);

        $action->handle(new CreateScheduleData(
            UserRole::SuperAdmin,
            $companyId,
            $companyId,
            'Office',
            '09:00',
            '18:00',
            10,
            60,
            ['monday'],
        ));
    }

    private function access(): AuthorizeActor
    {
        return new AuthorizeActor(new RolePermissions);
    }
}

final class MemoryScheduleRepository implements ScheduleRepository
{
    public function save(Schedule $schedule): Schedule
    {
        return $schedule;
    }

    public function find(CompanyId $companyId, int $scheduleId): ?Schedule
    {
        return null;
    }

    public function search(SearchSchedulesCriteria $criteria): array
    {
        return [];
    }
}
