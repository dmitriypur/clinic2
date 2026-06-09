<?php

namespace Tests\Unit;

use App\Enums\BlockType;
use App\Models\Block;
use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PageReadingTimeTest extends TestCase
{
    /** @test */
    public function it_calculates_reading_time_from_post_text_blocks_only(): void
    {
        $page = new Page();
        $page->setRelation('blocks', new Collection([
            new Block([
                'type' => BlockType::POST_TEXT,
                'body_html' => '<p>' . str_repeat('слово ', 100) . '</p>',
            ]),
            new Block([
                'type' => BlockType::POST_TEXT,
                'body_html' => '<div>' . str_repeat('текст&nbsp;', 101) . '</div>',
            ]),
            new Block([
                'type' => BlockType::FAQ,
                'body_html' => str_repeat('лишнее ', 500),
            ]),
        ]));

        $this->assertSame(2, $page->reading_time_minutes);
    }

    /** @test */
    public function it_returns_at_least_one_minute_for_an_empty_article(): void
    {
        $page = new Page();
        $page->setRelation('blocks', new Collection());

        $this->assertSame(1, $page->reading_time_minutes);
    }
}
