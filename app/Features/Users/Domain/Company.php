<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\Exceptions\CompanyIsNotPersisted;
use App\Features\Users\Domain\Exceptions\CompanyNameRequired;
use App\Features\Users\Domain\Exceptions\CompanySlugRequired;
use App\Features\Users\Domain\Exceptions\InvalidBranchLimit;
use App\Features\Users\Domain\Exceptions\InvalidEmployeeLimit;
use App\Features\Users\Domain\Exceptions\InvalidTimezone;
use App\Features\Users\Domain\Exceptions\PlanCodeRequired;
use DateTimeZone;
use Exception;

final readonly class Company
{
    private function __construct(
        private ?int $id,
        private string $name,
        private string $slug,
        private ?string $taxId,
        private int $planId,
        private string $planCode,
        private int $maxEmployees,
        private ?int $maxBranches,
        private bool $active,
        private string $timezone,
    ) {
        if ($this->id !== null && $this->id < 1) {
            throw new CompanyIsNotPersisted;
        }

        if ($this->name === '') {
            throw new CompanyNameRequired;
        }

        if ($this->slug === '') {
            throw new CompanySlugRequired;
        }

        if ($this->planId < 1) {
            throw new PlanCodeRequired;
        }

        if ($this->planCode === '') {
            throw new PlanCodeRequired;
        }

        if ($this->maxEmployees < 1) {
            throw new InvalidEmployeeLimit;
        }

        if ($this->maxBranches !== null && $this->maxBranches < 1) {
            throw new InvalidBranchLimit;
        }
    }

    public static function open(
        string $name,
        string $slug,
        ?string $taxId,
        int $planId,
        string $planCode,
        int $maxEmployees,
        ?int $maxBranches,
        bool $isActive,
        string $timezone,
    ): self {
        return new self(
            null,
            trim($name),
            self::normalizeSlug($slug),
            self::blankToNull($taxId),
            $planId,
            trim($planCode),
            $maxEmployees,
            $maxBranches,
            $isActive,
            self::normalizeTimezone($timezone),
        );
    }

    public static function slugFromName(string $name): string
    {
        $slug = mb_strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if ($slug === '') {
            throw new CompanySlugRequired;
        }

        return $slug;
    }

    public function id(): CompanyId
    {
        if ($this->id === null) {
            throw new CompanyIsNotPersisted;
        }

        return new CompanyId($this->id);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function taxId(): ?string
    {
        return $this->taxId;
    }

    public function planId(): int
    {
        return $this->planId;
    }

    public function planCode(): string
    {
        return $this->planCode;
    }

    public function maxEmployees(): int
    {
        return $this->maxEmployees;
    }

    public function maxBranches(): ?int
    {
        return $this->maxBranches;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->name,
            $this->slug,
            $this->taxId,
            $this->planId,
            $this->planCode,
            $this->maxEmployees,
            $this->maxBranches,
            $this->active,
            $this->timezone,
        );
    }

    public function withLimits(int $maxEmployees, ?int $maxBranches): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->slug,
            $this->taxId,
            $this->planId,
            $this->planCode,
            $maxEmployees,
            $maxBranches,
            $this->active,
            $this->timezone,
        );
    }

    private static function normalizeSlug(string $slug): string
    {
        $normalized = mb_strtolower(trim($slug));

        if ($normalized === '') {
            throw new CompanySlugRequired;
        }

        return $normalized;
    }

    private static function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }

    private static function normalizeTimezone(string $timezone): string
    {
        $value = trim($timezone);

        try {
            new DateTimeZone($value);
        } catch (Exception) {
            throw new InvalidTimezone;
        }

        return $value;
    }
}
