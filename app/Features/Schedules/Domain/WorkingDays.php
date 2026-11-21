<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain;

use App\Features\Schedules\Domain\Exceptions\DuplicateWorkingDay;
use App\Features\Schedules\Domain\Exceptions\InvalidWorkingDay;
use App\Features\Schedules\Domain\Exceptions\WorkingDaysRequired;

final readonly class WorkingDays
{
    /**
     * @param  list<Weekday>  $days
     */
    public function __construct(public array $days)
    {
        if ($this->days === []) {
            throw new WorkingDaysRequired;
        }

        $values = array_map(fn (Weekday $day): string => $day->value, $this->days);

        if (count($values) !== count(array_unique($values))) {
            throw new DuplicateWorkingDay;
        }
    }

    /**
     * @param  list<string>  $days
     */
    public static function fromStrings(array $days): self
    {
        $parsed = [];

        foreach ($days as $day) {
            $weekday = Weekday::tryFrom($day);

            if ($weekday === null) {
                throw new InvalidWorkingDay($day);
            }

            $parsed[] = $weekday;
        }

        return new self($parsed);
    }

    /**
     * @return list<string>
     */
    public function values(): array
    {
        return array_map(fn (Weekday $day): string => $day->value, $this->days);
    }
}
