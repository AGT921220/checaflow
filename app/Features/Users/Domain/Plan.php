<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

use App\Features\Users\Domain\Exceptions\EmployeeLimitOutsidePlan;
use App\Features\Users\Domain\Exceptions\InvalidEmployeeLimit;
use App\Features\Users\Domain\Exceptions\PlanIsNotPersisted;

final readonly class Plan
{
    public function __construct(
        private ?int $id,
        private string $code,
        private string $segment,
        private int $minEmployees,
        private ?int $maxEmployees,
        private string $billing,
        private int $pricePerUserMonthly,
        private ?int $pricePerUserMonthlyMax,
        private int $minimumTicket,
        private string $minimumTicketPeriod,
    ) {
        if ($this->id !== null && $this->id < 1) {
            throw new PlanIsNotPersisted;
        }

        if ($this->minEmployees < 1) {
            throw new InvalidEmployeeLimit;
        }

        if ($this->maxEmployees !== null && $this->maxEmployees < $this->minEmployees) {
            throw new InvalidEmployeeLimit;
        }
    }

    public function id(): int
    {
        if ($this->id === null) {
            throw new PlanIsNotPersisted;
        }

        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function segment(): string
    {
        return $this->segment;
    }

    public function minEmployees(): int
    {
        return $this->minEmployees;
    }

    public function maxEmployees(): ?int
    {
        return $this->maxEmployees;
    }

    public function billing(): string
    {
        return $this->billing;
    }

    public function pricePerUserMonthly(): int
    {
        return $this->pricePerUserMonthly;
    }

    public function pricePerUserMonthlyMax(): ?int
    {
        return $this->pricePerUserMonthlyMax;
    }

    public function minimumTicket(): int
    {
        return $this->minimumTicket;
    }

    public function minimumTicketPeriod(): string
    {
        return $this->minimumTicketPeriod;
    }

    public function assertAllows(int $maxEmployees): void
    {
        if ($maxEmployees < $this->minEmployees) {
            throw new EmployeeLimitOutsidePlan;
        }

        if ($this->maxEmployees !== null && $maxEmployees > $this->maxEmployees) {
            throw new EmployeeLimitOutsidePlan;
        }
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->code,
            $this->segment,
            $this->minEmployees,
            $this->maxEmployees,
            $this->billing,
            $this->pricePerUserMonthly,
            $this->pricePerUserMonthlyMax,
            $this->minimumTicket,
            $this->minimumTicketPeriod,
        );
    }
}
