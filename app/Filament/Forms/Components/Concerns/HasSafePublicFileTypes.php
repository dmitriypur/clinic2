<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components\Concerns;

trait HasSafePublicFileTypes
{
    public function safeImages(): static
    {
        return $this
            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
            ])
            ->rules(['extensions:jpg,jpeg,jfif,png,webp']);
    }

    public function safePng(): static
    {
        return $this
            ->acceptedFileTypes(['image/png'])
            ->rules(['extensions:png']);
    }

    public function safeDocuments(): static
    {
        return $this
            ->acceptedFileTypes(['application/pdf'])
            ->rules(['extensions:pdf']);
    }

    public function safeImagesAndDocuments(): static
    {
        return $this
            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/pdf',
            ])
            ->rules(['extensions:jpg,jpeg,jfif,png,webp,pdf']);
    }

    public function safeVideos(): static
    {
        return $this
            ->acceptedFileTypes(['video/mp4'])
            ->rules(['extensions:mp4']);
    }

    public function safePublicFiles(): static
    {
        return $this
            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/pdf',
                'video/mp4',
            ])
            ->rules(['extensions:jpg,jpeg,jfif,png,webp,pdf,mp4']);
    }
}
