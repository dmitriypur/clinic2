<?php

namespace Tests\Feature;

use App\Models\Doctor;
use Illuminate\Support\Facades\Blade;
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
}
