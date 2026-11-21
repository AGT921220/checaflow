<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

interface UserRepository
{
    public function findByEmail(string $email): ?PlatformUser;

    public function findByNickname(string $nickname): ?PlatformUser;

    public function find(int $userId): ?PlatformUser;

    public function save(PlatformUser $user): PlatformUser;
}
