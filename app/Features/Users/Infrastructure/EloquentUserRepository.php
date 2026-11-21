<?php

declare(strict_types=1);

namespace App\Features\Users\Infrastructure;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\Exceptions\EmailAlreadyUsed;
use App\Features\Users\Domain\Exceptions\NicknameAlreadyUsed;
use App\Features\Users\Domain\Exceptions\UserIsNotPersisted;
use App\Features\Users\Domain\PlatformUser;
use App\Features\Users\Domain\UserRepository;
use App\Features\Users\Domain\UserRole;
use App\Models\User as UserRecord;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentUserRepository implements UserRepository
{
    public function findByEmail(string $email): ?PlatformUser
    {
        $record = UserRecord::query()
            ->where('email', mb_strtolower(trim($email)))
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->map($record);
    }

    public function findByNickname(string $nickname): ?PlatformUser
    {
        $record = UserRecord::query()
            ->where('nickname', mb_strtolower(trim($nickname)))
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->map($record);
    }

    public function find(int $userId): ?PlatformUser
    {
        $record = UserRecord::query()->find($userId);

        if ($record === null) {
            return null;
        }

        return $this->map($record);
    }

    public function save(PlatformUser $user): PlatformUser
    {
        $record = $this->record($user);
        $record->company_id = $user->companyId()?->value;
        $record->name = $user->name();
        $record->first_name = $user->firstName();
        $record->last_name = $user->lastName();
        $record->nickname = $user->nickname();
        $record->email = $user->email();
        $record->phone = $user->phone();
        $record->photo_path = $user->photoPath();
        $record->role = $user->role()->value;
        $record->is_active = $user->isActive();

        if ($user->plainPassword() !== null) {
            $record->password = $user->plainPassword();
        }

        try {
            $record->save();
        } catch (UniqueConstraintViolationException $exception) {
            $this->raiseUniqueConflict($exception, $user);
        }

        return $this->map($record);
    }

    private function record(PlatformUser $user): UserRecord
    {
        try {
            $id = $user->id();
        } catch (UserIsNotPersisted) {
            return new UserRecord;
        }

        $record = UserRecord::query()->find($id);

        if ($record === null) {
            throw new UserIsNotPersisted;
        }

        return $record;
    }

    private function map(UserRecord $record): PlatformUser
    {
        $companyId = null;

        if ($record->company_id !== null) {
            $companyId = new CompanyId((int) $record->company_id);
        }

        return new PlatformUser(
            (int) $record->id,
            $companyId,
            (string) $record->name,
            $record->first_name !== null ? (string) $record->first_name : null,
            $record->last_name !== null ? (string) $record->last_name : null,
            (string) ($record->nickname ?? ''),
            $record->email !== null ? (string) $record->email : null,
            $record->phone !== null ? (string) $record->phone : null,
            $record->photo_path !== null ? (string) $record->photo_path : null,
            UserRole::from((string) $record->role),
            (bool) $record->is_active,
            null,
        );
    }

    private function raiseUniqueConflict(UniqueConstraintViolationException $exception, PlatformUser $user): never
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'nickname')) {
            throw new NicknameAlreadyUsed;
        }

        if (str_contains($message, 'email') || $user->email() !== null) {
            throw new EmailAlreadyUsed;
        }

        throw new NicknameAlreadyUsed;
    }
}
