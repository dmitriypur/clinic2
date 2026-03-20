<?php

namespace Tests\Feature;

use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorsPublicPageSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_doctors_index_order_uses_manual_page_sort_order_first(): void
    {
        $doctorA = $this->createDoctor('Иванов', 'Иван', 2);
        $doctorB = $this->createDoctor('Петров', 'Пётр', 1);
        $doctorC = $this->createDoctor('Сидоров', 'Сидор', null);

        $orderedIds = Doctor::query()
            ->orderedForPublicIndex()
            ->pluck('id')
            ->all();

        $this->assertSame([
            $doctorB->id,
            $doctorA->id,
            $doctorC->id,
        ], $orderedIds);
    }

    private function createDoctor(string $surname, string $name, ?int $pageSortOrder): Doctor
    {
        return Doctor::query()->create([
            'uuid' => (string) fake()->uuid(),
            'surname' => $surname,
            'name' => $name,
            'speciality' => 'Офтальмолог',
            'job_title' => 'Офтальмолог',
            'page_sort_order' => $pageSortOrder,
            'excerpt' => 'Описание',
            'bio' => 'Биография',
            'handle' => strtolower($surname . '-' . $name),
        ]);
    }
}
