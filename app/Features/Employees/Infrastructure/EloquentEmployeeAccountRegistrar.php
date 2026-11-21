<?php

declare(strict_types=1);

namespace App\Features\Employees\Infrastructure;

use App\Features\Employees\Domain\EmployeeAccountRegistrar;
use App\Features\Employees\Domain\Exceptions\EmailAlreadyUsed;
use App\Features\Employees\Domain\Exceptions\NicknameAlreadyUsed;
use App\Features\Shared\Domain\CompanyId;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentEmployeeAccountRegistrar implements EmployeeAccountRegistrar
{
    public function register(
        CompanyId $companyId,
        string $name,
        string $nickname,
        ?string $email,
        ?string $phone,
        string $password,
    ): int {
        $nickname = mb_strtolower(trim($nickname));
        $email = $this->normalizeEmail($email);
        $phone = $this->normalizePhone($phone);

        try {
            $user = User::query()->create([
                'company_id' => $companyId->value,
                'name' => trim($name),
                'nickname' => $nickname,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'role' => 'employee',
                'is_active' => true,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            $this->raiseUniqueConflict($exception);
        }

        return (int) $user->id;
    }

    private function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = mb_strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        return $email;
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        return $phone;
    }

    private function raiseUniqueConflict(UniqueConstraintViolationException $exception): never
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'nickname') || str_contains($message, 'users_nickname')) {
            throw new NicknameAlreadyUsed;
        }

        throw new EmailAlreadyUsed;
    }
}
