<?php

declare(strict_types=1);

namespace App\Features\Users\Infrastructure;

use App\Features\Users\Domain\ProfilePhotoStore;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PublicProfilePhotoStore implements ProfilePhotoStore
{
    public function put(int $userId, string $binary, string $extension): string
    {
        $path = 'profile-photos/'.$userId.'/'.Str::uuid()->toString().'.'.$extension;
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        if (! Storage::disk('public')->exists($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
