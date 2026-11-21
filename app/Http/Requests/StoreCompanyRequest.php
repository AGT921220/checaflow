<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'plan_id' => ['required', 'integer', 'min:1', Rule::exists('plans', 'id')],
            'max_employees' => ['required', 'integer', 'min:1'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'nickname' => ['required', 'string', 'max:255', 'alpha_dash:ascii'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:4'],
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

        if ($payload === []) {
            return;
        }

        $this->merge($payload);
    }
}
