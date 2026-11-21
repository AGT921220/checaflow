<?php

declare(strict_types=1);

namespace App\Features\Users\Domain;

interface ProfilePhotoStore
{
    /**
     * Stores binary image contents and returns a path relative to the public disk.
     */
    public function put(int $userId, string $binary, string $extension): string;

    public function delete(?string $path): void;
}
