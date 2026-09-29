<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Doctor;
use App\Services\CityService;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuDoctorPreviewUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_preview_uses_the_same_city_path_as_the_menu_link(): void
    {
        $moscow = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
        ]);
        $kirov = City::query()->create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => false,
            'active' => true,
        ]);
        $doctor = Doctor::query()->create([
            'uuid' => (string) fake()->uuid(),
            'name' => 'Иван',
            'surname' => 'Иванов',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Офтальмолог',
            'excerpt' => 'Описание',
            'bio' => 'Биография',
            'handle' => 'ivanov',
        ]);
        $doctor->cities()->attach([$moscow->id, $kirov->id]);

        foreach ([
            [$kirov, '/kirov/doctors/'.$doctor->id],
            [$moscow, '/doctors/'.$doctor->id],
        ] as [$city, $expectedPath]) {
            app(CityService::class)->setCurrentCity($city);

            $item = app(MenuService::class)->prepareItems([[
                'label' => 'Иванов',
                'type' => 'doctor',
                'data' => ['id' => $doctor->id],
                'children' => [],
            ]])[0];

            $this->assertSame($expectedPath, $item['data']['url']);
            $this->assertSame($expectedPath, parse_url($item['data']['doctor']['url'], PHP_URL_PATH));
        }
    }
}
