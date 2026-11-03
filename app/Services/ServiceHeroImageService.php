<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Throwable;

final class ServiceHeroImageService
{
    public function store(UploadedFile $file): string
    {
        $disk = Storage::disk('public');
        $name = Str::ulid().'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storePubliclyAs('service-hero', $name, 'public');

        try {
            $this->createWebp($path, $disk);
        } catch (Throwable $exception) {
            $disk->delete([$path, $this->webpPath($path)]);
            throw $exception;
        }

        return $path;
    }

    public function createWebp(string $path, ?Filesystem $disk = null): string
    {
        $disk ??= Storage::disk('public');
        $webp = $this->webpPath($path);
        if ($webp === $path || $disk->exists($webp)) {
            return $webp;
        }

        $encoded = (new ImageManager(['driver' => 'gd']))->make($disk->get($path))->orientate()->encode('webp', 85);
        if (! $disk->put($webp, (string) $encoded, 'public')) {
            throw new \RuntimeException('Не удалось сохранить WebP баннера.');
        }

        return $webp;
    }

    public function webpPath(string $path): string
    {
        return preg_replace('/\.[^.\/]+$/', '', $path).'.webp';
    }

    public function webpUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $webp = $this->webpPath($path);

        return Storage::disk('public')->exists($webp) ? '/storage/'.ltrim($webp, '/') : null;
    }
}
