<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

interface PlanRepository
{
    public function find(int $planId): ?Plan;

    /**
     * @return list<Plan>
     */
    public function all(): array;
}
