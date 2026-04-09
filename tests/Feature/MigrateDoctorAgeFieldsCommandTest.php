<?php

namespace Tests\Feature;

use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrateDoctorAgeFieldsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_migrates_legacy_receives_into_structured_fields_and_receives_text(): void
    {
        $doctor = Doctor::create([
            'uuid' => '00000000-0000-0000-0000-000000000201',
            'name' => 'Иван',
            'surname' => 'Петров',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Врач-офтальмолог',
            'excerpt' => 'Кратко',
            'bio' => 'Подробно',
            'extra' => [
                'receives' => 'Ведет прием с 3 до 17 лет',
            ],
        ]);

        $this->artisan('doctors:migrate-age-fields')
            ->expectsOutputToContain('Перенос age fields завершён.')
            ->assertSuccessful();

        $doctor->refresh();

        $this->assertSame(36, data_get($doctor->extra, 'age_min_months'));
        $this->assertSame(204, data_get($doctor->extra, 'age_max_months'));
        $this->assertSame('Ведет прием с 3 до 17 лет', data_get($doctor->extra, 'receives_text'));
    }

    public function test_command_does_not_overwrite_already_configured_structured_fields(): void
    {
        $doctor = Doctor::create([
            'uuid' => '00000000-0000-0000-0000-000000000202',
            'name' => 'Мария',
            'surname' => 'Иванова',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Врач-офтальмолог',
            'excerpt' => 'Кратко',
            'bio' => 'Подробно',
            'extra' => [
                'receives' => 'Ведет прием с 3 до 17 лет',
                'age_min_months' => 1,
                'age_max_months' => 24,
                'receives_text' => 'Свой текст',
            ],
        ]);

        $this->artisan('doctors:migrate-age-fields')
            ->assertSuccessful();

        $doctor->refresh();

        $this->assertSame(1, data_get($doctor->extra, 'age_min_months'));
        $this->assertSame(24, data_get($doctor->extra, 'age_max_months'));
        $this->assertSame('Свой текст', data_get($doctor->extra, 'receives_text'));
    }
}
