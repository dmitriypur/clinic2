<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Review;
use App\Services\YmlFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class YmlFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_yml_feed_generation(): void
    {
        // Создаем тестового врача
        $doctor = Doctor::factory()->create([
            'name' => 'Иван Иванович',
            'surname' => 'Иванов',
            'speciality' => 'Офтальмолог',
            'excerpt' => 'Тестовое описание врача',
            'extra' => [
                'seniority' => '10 лет',
                'category' => 'Первая',
                'education' => [
                    [
                        'title' => 'Медицинский университет',
                        'educational_institution' => [
                            [
                                'year' => '2010',
                                'specialty' => 'Лечебное дело',
                                'level' => 'Специалитет'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // Создаем тестовый отзыв
        Review::factory()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Тестовый пациент',
            'body_html' => 'Отличный врач!',
            'rating' => 5
        ]);

        $service = app(YmlFeedService::class);
        $feedContent = $service->generateDoctorsFeed();

        // Проверяем, что фид содержит основные элементы
        $this->assertStringContainsString('<yml_catalog version="2.0"', $feedContent);
        $this->assertStringContainsString('<shop>', $feedContent);
        $this->assertStringContainsString('<doctors>', $feedContent);
        $this->assertStringContainsString('<clinics>', $feedContent);
        $this->assertStringContainsString('<services>', $feedContent);
        $this->assertStringContainsString('<offers>', $feedContent);
        
        // Проверяем данные врача
        $this->assertStringContainsString('Иванов Иван Иванович', $feedContent);
        $this->assertStringContainsString('Офтальмолог', $feedContent);
        $this->assertStringContainsString('Тестовое описание врача', $feedContent);
        
        // Проверяем отзыв
        $this->assertStringContainsString('Тестовый пациент', $feedContent);
        $this->assertStringContainsString('Отличный врач!', $feedContent);
    }

    public function test_yml_feed_file_saving(): void
    {
        Storage::fake('public');

        $service = app(YmlFeedService::class);
        $feedContent = $service->generateDoctorsFeed();
        $filename = $service->saveFeedToFile($feedContent);

        $this->assertSame('doctors_feed.xml', $filename);

        // Проверяем, что файл существует
        $this->assertTrue(Storage::disk('public')->exists($filename));

        // Проверяем содержимое файла
        $savedContent = Storage::disk('public')->get($filename);
        $this->assertEquals($feedContent, $savedContent);
    }
}
