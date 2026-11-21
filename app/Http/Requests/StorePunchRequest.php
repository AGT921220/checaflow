<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Features\Attendance\Domain\PunchType;
use App\Features\Shared\Domain\GeoPoint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePunchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'punch_type' => ['required', Rule::enum(PunchType::class)],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ];
    }

    public function location(): ?GeoPoint
    {
        if (! $this->filled('latitude') || ! $this->filled('longitude')) {
            return null;
        }

        return new GeoPoint((float) $this->input('latitude'), (float) $this->input('longitude'));
    }
}
