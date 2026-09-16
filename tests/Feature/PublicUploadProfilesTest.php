<?php

declare(strict_types=1);

namespace Tests\Feature;

use Awcodes\Curator\Resources\MediaResource;
use ReflectionObject;
use Tests\TestCase;

class PublicUploadProfilesTest extends TestCase
{
    public function test_curator_uploader_checks_the_original_extension(): void
    {
        $uploader = MediaResource::getUploaderField();
        $reflection = new ReflectionObject($uploader);
        $rulesProperty = $reflection->getProperty('rules');
        $rulesProperty->setAccessible(true);
        $rules = collect($rulesProperty->getValue($uploader))
            ->pluck(0)
            ->filter(fn (mixed $rule): bool => is_string($rule))
            ->values()
            ->all();

        $this->assertContains(
            'extensions:jpg,jpeg,jfif,png,webp,pdf',
            $rules,
        );
    }
}
