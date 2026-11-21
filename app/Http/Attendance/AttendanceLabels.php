<?php

declare(strict_types=1);

namespace App\Http\Attendance;

final class AttendanceLabels
{
    private const STATUS = [
        'on_time' => 'A tiempo',
        'late' => 'Tarde',
        'incomplete' => 'Incompleto',
        'absent' => 'Ausente',
    ];

    private const PUNCH = [
        'clock_in' => 'Entrada',
        'lunch_out' => 'Salir a comer',
        'lunch_in' => 'Regresar de comer',
        'clock_out' => 'Salida',
    ];

    public function status(string $value): string
    {
        return self::STATUS[$value] ?? $value;
    }

    public function punch(string $value): string
    {
        return self::PUNCH[$value] ?? $value;
    }

    public function action(?string $next): string
    {
        if ($next === null) {
            return 'Día completo';
        }

        return $this->punch($next);
    }
}
