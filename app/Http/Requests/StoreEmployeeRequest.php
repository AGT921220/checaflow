<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreEmployeeRequest extends FormRequest
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'employee_code' => ['required', 'string', 'max:255'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['required', 'integer', 'min:1'],
            'schedule_id' => ['required', 'integer', 'min:1'],
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

            if ($email === '') {
                $payload['email'] = null;
            }

            if ($email !== '') {
                $payload['email'] = mb_strtolower($email);
            }
        }

        if ($this->has('phone')) {
            $phone = trim((string) $this->input('phone'));

            if ($phone === '') {
                $payload['phone'] = null;
            }

            if ($phone !== '') {
                $payload['phone'] = $phone;
            }
        }

        if ($payload === []) {
            return;
        }

        $this->merge($payload);
    }
}
