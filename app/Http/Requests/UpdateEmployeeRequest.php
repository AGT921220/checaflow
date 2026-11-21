<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['required', 'string', 'max:255', 'alpha_dash:ascii'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'employee_code' => ['required', 'string', 'max:255'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['required', 'integer', 'min:1'],
            'schedule_id' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
            'photo' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->has('nickname')) {
            $payload['nickname'] = mb_strtolower(trim((string) $this->input('nickname')));
        }

        if ($this->has('email')) {
            $email = trim((string) $this->input('email'));
            $payload['email'] = $email === '' ? null : mb_strtolower($email);
        }

        if ($this->has('phone')) {
            $phone = trim((string) $this->input('phone'));
            $payload['phone'] = $phone === '' ? null : $phone;
        }

        if ($payload === []) {
            return;
        }

        $this->merge($payload);
    }
}
