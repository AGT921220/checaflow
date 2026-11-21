<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\Exceptions\ForbiddenUserRole;
use App\Features\Users\Domain\Exceptions\InvalidUserReference;
use App\Features\Users\Domain\Exceptions\NicknameRequired;
use App\Features\Users\Domain\Exceptions\UserIsNotPersisted;

final readonly class PlatformUser
{
    public function __construct(
        private ?int $id,
        private ?CompanyId $companyId,
        private string $name,
        private ?string $firstName,
        private ?string $lastName,
        private string $nickname,
        private ?string $email,
        private ?string $phone,
        private ?string $photoPath,
        private UserRole $role,
        private bool $isActive,
        private ?string $plainPassword,
    ) {
        if ($this->id !== null && $this->id < 1) {
            throw new InvalidUserReference;
        }

        if ($this->nickname === '') {
            throw new NicknameRequired;
        }

        if ($this->role === UserRole::SuperAdmin && $this->companyId !== null) {
            throw new ForbiddenUserRole;
        }

        if ($this->role !== UserRole::SuperAdmin && $this->companyId === null) {
            throw new ForbiddenUserRole;
        }
    }

    public static function register(
        ?CompanyId $companyId,
        string $firstName,
        string $lastName,
        string $nickname,
        ?string $email,
        ?string $phone,
        UserRole $role,
        bool $isActive,
        ?string $plainPassword,
        ?string $photoPath = null,
    ): self {
        $first = trim($firstName);
        $last = trim($lastName);

        return new self(
            null,
            $companyId,
            trim($first.' '.$last),
            self::blankToNull($first),
            self::blankToNull($last),
            mb_strtolower(trim($nickname)),
            self::normalizeEmail($email),
            self::blankToNull($phone),
            self::blankToNull($photoPath),
            $role,
            $isActive,
            $plainPassword,
        );
    }

    public function id(): int
    {
        if ($this->id === null) {
            throw new UserIsNotPersisted;
        }

        return $this->id;
    }

    public function companyId(): ?CompanyId
    {
        return $this->companyId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function firstName(): ?string
    {
        return $this->firstName;
    }

    public function lastName(): ?string
    {
        return $this->lastName;
    }

    public function nickname(): string
    {
        return $this->nickname;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function photoPath(): ?string
    {
        return $this->photoPath;
    }

    public function role(): UserRole
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function plainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function belongsTo(CompanyId $companyId): bool
    {
        return $this->companyId !== null && $this->companyId->equals($companyId);
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->companyId,
            $this->name,
            $this->firstName,
            $this->lastName,
            $this->nickname,
            $this->email,
            $this->phone,
            $this->photoPath,
            $this->role,
            $this->isActive,
            $this->plainPassword,
        );
    }

    public function withActive(bool $isActive): self
    {
        return new self(
            $this->id,
            $this->companyId,
            $this->name,
            $this->firstName,
            $this->lastName,
            $this->nickname,
            $this->email,
            $this->phone,
            $this->photoPath,
            $this->role,
            $isActive,
            $this->plainPassword,
        );
    }

    public function withContact(
        string $name,
        ?string $firstName,
        ?string $lastName,
        string $nickname,
        ?string $email,
        ?string $phone,
    ): self {
        $first = self::blankToNull($firstName);
        $last = self::blankToNull($lastName);
        $display = trim($name);

        if ($display === '' && ($first !== null || $last !== null)) {
            $display = trim(($first ?? '').' '.($last ?? ''));
        }

        return new self(
            $this->id,
            $this->companyId,
            $display,
            $first,
            $last,
            mb_strtolower(trim($nickname)),
            self::normalizeEmail($email),
            self::blankToNull($phone),
            $this->photoPath,
            $this->role,
            $this->isActive,
            $this->plainPassword,
        );
    }

    public function withPhotoPath(?string $photoPath): self
    {
        return new self(
            $this->id,
            $this->companyId,
            $this->name,
            $this->firstName,
            $this->lastName,
            $this->nickname,
            $this->email,
            $this->phone,
            self::blankToNull($photoPath),
            $this->role,
            $this->isActive,
            $this->plainPassword,
        );
    }

    private static function normalizeEmail(?string $email): ?string
    {
        $value = self::blankToNull($email);

        if ($value === null) {
            return null;
        }

        return mb_strtolower($value);
    }

    private static function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }
}
