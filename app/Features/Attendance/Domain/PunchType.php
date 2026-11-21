<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

enum PunchType: string
{
    case ClockIn = 'clock_in';
    case LunchOut = 'lunch_out';
    case LunchIn = 'lunch_in';
    case ClockOut = 'clock_out';

    public function next(): ?self
    {
        return match ($this) {
            self::ClockIn => self::LunchOut,
            self::LunchOut => self::LunchIn,
            self::LunchIn => self::ClockOut,
            self::ClockOut => null,
        };
    }
}
