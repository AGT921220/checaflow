<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Features\Schedules\Domain\Weekday;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'clock_in_time' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'clock_out_time' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:65535'],
            'allowed_lunch_minutes' => ['required', 'integer', 'min:0', 'max:65535'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['required', Rule::enum(Weekday::class)],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
