<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'required_if:require_geofence,1', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_if:require_geofence,1', 'numeric', 'between:-180,180'],
            'geofence_radius_meters' => ['nullable', 'required_if:require_geofence,1', 'integer', 'min:1'],
            'require_geofence' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
