<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MediaUploadService
{
    /**
     * Upload, strip EXIF metadata, and re-encode image to WebP (BE-013, SEC-011).
     */
    public function uploadAndOptimize(UploadedFile $file, string $directory = 'cms'): string
    {
        $uuid = (string) Str::uuid();
        $path = trim($directory, '/') . "/{$uuid}.webp";

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());
        $encoded = (string) $image->toWebp(85);

        Storage::disk('public')->put($path, $encoded);

        return $path;
    }

    /**
     * Delete file from public disk if present.
     */
    public function delete(?string $path): bool
    {
        if ($path !== null && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }
}
