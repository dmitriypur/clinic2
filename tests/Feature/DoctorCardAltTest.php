<?php

namespace Tests\Feature;

use App\Models\Doctor;
use Illuminate\Support\Facades\Blade;
use Spatie\MediaLibrary\MediaCollections\HtmlableMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class DoctorCardAltTest extends TestCase
{
    public function test_it_renders_when_doctor_extra_is_null(): void
    {
        $doctor = $this->doctorWithNullExtra();

        $html = Blade::render(
            '<x-doctor-card-alt :doctor="$doctor" />',
            ['doctor' => $doctor],
        );

        $this->assertStringContainsString('Иванов', $html);
        $this->assertStringContainsString('Врачебный стаж:', $html);
    }

    public function test_legacy_doctor_card_renders_when_doctor_extra_is_null(): void
    {
        $doctor = $this->doctorWithNullExtra();

        $html = Blade::render(
            '<x-doctor-card :doctor="$doctor" />',
            ['doctor' => $doctor],
        );

        $this->assertStringContainsString('Иванов', $html);
        $this->assertStringContainsString('Врачебный стаж:', $html);
    }

    public function test_extra_information_filled_is_false_when_doctor_extra_is_null(): void
    {
        $doctor = $this->doctorWithNullExtra();

        $this->assertFalse($doctor->extra_information_filled);
    }

    public function test_extra_information_filled_is_false_when_only_promo_exclusion_flag_exists(): void
    {
        $doctor = $this->doctorWithNullExtra();
        $doctor->extra = ['exclude_from_branch_promo_price' => false];

        $this->assertFalse($doctor->extra_information_filled);
    }

    public function test_doctors_alt_card_lazy_loads_the_avatar(): void
    {
        $doctor = $this->doctorWithAvatar();

        $html = Blade::render(
            '<x-doctor-card-alt :doctor="$doctor" />',
            ['doctor' => $doctor],
        );

        $this->assertMatchesRegularExpression('/<img[^>]+loading="lazy"[^>]*>/', $html);
    }

    public function test_doctors_index_card_lazy_loads_the_avatar(): void
    {
        $doctor = $this->doctorWithAvatar();

        $html = view('components.page.partials.doctor-card', ['doctor' => $doctor])->render();

        $this->assertMatchesRegularExpression('/<img[^>]+loading="lazy"[^>]*>/', $html);
    }

    public function test_main_avatar_conversion_does_not_depend_on_the_queue(): void
    {
        config()->set('media-library.queue_conversions_by_default', true);

        $doctor = new Doctor();
        $doctor->registerMediaConversions();

        $mainConversion = collect($doctor->mediaConversions)
            ->first(fn ($conversion) => $conversion->getName() === 'main');

        $this->assertNotNull($mainConversion);
        $this->assertFalse($mainConversion->shouldBeQueued());
    }

    private function doctorWithNullExtra(): Doctor
    {
        $doctor = new Doctor([
            'uuid' => '00000000-0000-4000-8000-000000000001',
            'name' => 'Иван',
            'surname' => 'Иванов',
            'speciality' => 'Офтальмолог',
            'extra' => null,
        ]);
        $doctor->setAttribute('id', 'doctor-id');
        $doctor->setRelation('media', collect());

        return $doctor;
    }

    private function doctorWithAvatar(): Doctor
    {
        $doctor = new class([
            'uuid' => '00000000-0000-4000-8000-000000000001',
            'name' => 'Иван',
            'surname' => 'Иванов',
            'speciality' => 'Офтальмолог',
            'extra' => null,
            'handle' => 'ivanov',
        ]) extends Doctor {
            public ?HtmlableMedia $testAvatarImage = null;

            public function getAvatarImageAttribute(): ?HtmlableMedia
            {
                return $this->testAvatarImage;
            }
        };
        $doctor->setAttribute('id', 'doctor-id');

        $media = new Media([
            'name' => 'doctor-avatar',
            'file_name' => 'doctor-avatar.png',
            'mime_type' => 'image/png',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'collection_name' => 'default',
            'generated_conversions' => [],
            'updated_at' => now(),
        ]);
        $media->setAttribute('id', 1);
        $doctor->testAvatarImage = $media->img();

        return $doctor;
    }
}
