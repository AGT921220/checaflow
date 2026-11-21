<?php

declare(strict_types=1);

namespace App\Features\Users\Infrastructure;

use App\Features\Users\Domain\Plan;
use App\Features\Users\Domain\PlanRepository;
use App\Models\Plan as PlanRecord;

final class EloquentPlanRepository implements PlanRepository
{
    public function find(int $planId): ?Plan
    {
        $record = PlanRecord::query()->find($planId);

        if ($record === null) {
            return null;
        }

        return $this->map($record);
    }

    public function all(): array
    {
        return array_values(PlanRecord::query()
            ->orderBy('min_employees')
            ->get()
            ->map(fn (PlanRecord $record): Plan => $this->map($record))
            ->all());
    }

    private function map(PlanRecord $record): Plan
    {
        return new Plan(
            (int) $record->id,
            (string) $record->code,
            (string) $record->segment,
            (int) $record->min_employees,
            $record->max_employees !== null ? (int) $record->max_employees : null,
            (string) $record->billing,
            (int) $record->price_per_user_monthly,
            $record->price_per_user_monthly_max !== null ? (int) $record->price_per_user_monthly_max : null,
            (int) $record->minimum_ticket,
            (string) $record->minimum_ticket_period,
        );
    }
}
