<?php

namespace Tests\Feature;

use App\Models\Block;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ReviewsAltRenderTest extends TestCase
{
    /**
     * @dataProvider reviewsAltRenderCases
     */
    public function test_reviews_alt_collection_is_resolved_once_and_keeps_slider_controls(int $reviewsCount, int $expectedSlides, bool $hasControls): void
    {
        $block = $this->makeBlock($reviewsCount);
        View::share('address', 'Тестовый адрес');

        $html = view('components.block.reviews-alt', ['block' => $block])->render();

        $this->assertSame(1, $block->reviewsAltAccesses);
        $this->assertStringContainsString('Отзывы родителей', $html);
        $this->assertSame($expectedSlides, substr_count($html, 'swiper-slide mb-2'));

        if ($expectedSlides > 0) {
            $this->assertStringContainsString('Отзыв 1', $html);
            $this->assertStringContainsString('https://example.test/review', $html);
        }

        if ($hasControls) {
            $this->assertStringContainsString('reviews-alt-swiper-prev', $html);
            $this->assertStringContainsString('reviews-alt-swiper-next', $html);
        } else {
            $this->assertStringNotContainsString('reviews-alt-swiper-prev', $html);
            $this->assertStringNotContainsString('reviews-alt-swiper-next', $html);
        }
    }

    public static function reviewsAltRenderCases(): array
    {
        return [
            'no reviews' => [0, 0, false],
            'three reviews' => [3, 3, false],
            'four reviews' => [4, 4, true],
            'twelve reviews' => [12, 10, true],
        ];
    }

    private function makeBlock(int $reviewsCount): Block
    {
        $reviews = new Collection;

        if ($reviewsCount > 0) {
            foreach (range(1, $reviewsCount) as $number) {
                $reviews->push(new class($number)
                {
                    public bool $resource = false;
                    public ?string $doctor = null;
                    public array $resources = [];
                    public string $doctorInitials = '';
                    public string $get_date = '2026-09-01';
                    public Carbon $created_at;
                    public int $rating = 5;
                    public string $link_resource = 'https://example.test/review';
                    public string $name;
                    public string $body_html = '<p>Текст отзыва</p>';

                    public function __construct(int $number)
                    {
                        $this->created_at = Carbon::parse('2026-09-01');
                        $this->name = "Отзыв {$number}";
                    }

                    public function resolvedPages(): Collection
                    {
                        return new Collection;
                    }
                });
            }
        }

        $block = new class extends Block
        {
            public int $reviewsAltAccesses = 0;
            public Collection $testReviews;

            public function getReviewsAltAttribute(): ?Collection
            {
                $this->reviewsAltAccesses++;

                return $this->testReviews;
            }
        };

        $block->testReviews = $reviews;
        $block->title = 'Отзывы родителей';
        $block->settings = [];

        return $block;
    }
}
