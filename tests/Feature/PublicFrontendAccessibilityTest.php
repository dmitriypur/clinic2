<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Page;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFrontendAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_layout_has_one_main_landmark_and_a_skip_link(): void
    {
        $this->createPublicCityAndHomePage();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<a[^>]*href="#main-content"[^>]*>\s*Перейти к основному содержимому\s*<\/a>/u', $html);
        $this->assertMatchesRegularExpression('/<main\b[^>]*\bid="main-content"[^>]*\btabindex="-1"[^>]*>/i', $html);
        $this->assertSame(1, preg_match_all('/<main\b/i', $html));
    }

    public function test_layout_without_header_and_footer_keeps_the_main_landmark(): void
    {
        $this->createPublicCityAndHomePage();

        $html = view('form')->render();

        $this->assertMatchesRegularExpression('/<main\b[^>]*\bid="main-content"[^>]*>/i', $html);
        $this->assertSame(1, preg_match_all('/<main\b/i', $html));
        $this->assertDoesNotMatchRegularExpression('/<header\b/i', $html);
        $this->assertDoesNotMatchRegularExpression('/<footer\b/i', $html);
    }

    public function test_external_social_and_review_links_keep_seo_tokens_and_add_noopener(): void
    {
        $socialLinks = [
            'youtube' => 'https://youtube.example/channel',
            'telegram' => 'https://t.me/example',
            'vk' => 'https://vk.example/clinic',
            'rutube' => 'https://rutube.example/channel',
            'vk_video' => 'https://vkvideo.example/channel',
        ];
        $contactPanel = view('components.contact-panel', [
            'phone' => '+7 (000) 000-00-00',
            'email' => 'clinic@example.test',
            'address' => 'Киров',
            'schedule' => 'Ежедневно',
            'socials' => $socialLinks,
        ])->render();

        foreach ($socialLinks as $url) {
            $this->assertLinkHasRelTokens($contactPanel, $url, ['nofollow', 'noopener']);
        }

        $review = new Review([
            'name' => 'Пациент',
            'rating' => 0,
            'link_resource' => 'https://reviews.example/review/1',
            'body_html' => '<p>Отзыв</p>',
        ]);
        $review->created_at = now();

        $reviewCard = view('components.review-card', [
            'review' => $review,
            'address' => 'Киров',
        ])->render();

        $this->assertLinkHasRelTokens($reviewCard, 'https://reviews.example/review/1', ['noopener']);
    }

    public function test_repeated_video_icons_do_not_render_document_wide_ids(): void
    {
        $html = view('components.icon-rutube')->render()
            . view('components.icon-rutube')->render()
            . view('components.icon-vkvideo')->render()
            . view('components.icon-vkvideo')->render();

        $this->assertDoesNotMatchRegularExpression('/\sid="[^"]+"/', $html);
        $this->assertSame(4, substr_count($html, '<svg'));
    }

    private function createPublicCityAndHomePage(): void
    {
        City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
            'details' => [[
                'name' => 'Тестовая клиника',
                'fullname' => 'ООО «Тестовая клиника»',
            ]],
        ]);
        Page::query()->create([
            'title' => 'Главная',
            'handle' => '/',
            'active' => true,
        ]);
    }

    private function assertLinkHasRelTokens(string $html, string $url, array $tokens): void
    {
        $this->assertMatchesRegularExpression(
            '/<a\b[^>]*href="' . preg_quote($url, '/') . '"[^>]*target="_blank"[^>]*>/i',
            $html,
        );
        preg_match('/<a\b[^>]*href="' . preg_quote($url, '/') . '"[^>]*>/i', $html, $matches);
        preg_match('/\brel="([^"]*)"/i', $matches[0], $rel);

        foreach ($tokens as $token) {
            $this->assertContains($token, preg_split('/\s+/', $rel[1]));
        }
    }
}
