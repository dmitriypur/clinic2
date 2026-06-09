<?php

namespace Tests\Unit;

use App\Enums\BlockType;
use App\Models\Block;
use App\Models\City;
use App\Models\Page;
use App\Services\CityService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PageCitySeoVariablesTest extends TestCase
{
    /** @test */
    public function it_resolves_city_variables_in_loaded_blocks_recursively()
    {
        $city = new City([
            'name' => 'Симферополь',
            'phone' => '+7 (978) 000-00-00',
            'seo_cases' => [
                'prepositional' => 'в Симферополе',
                'genitive' => 'Симферополя',
                'accusative' => 'Симферополь',
            ],
        ]);

        app(CityService::class)->setCurrentCity($city);

        $block = new Block([
            'type' => BlockType::TEXT_BLOCKS,
            'title' => 'Лечение в {city}',
            'body_html' => '<p>Запишитесь в {city_prepositional}</p>',
            'payload' => [
                'subtitle' => 'Телефон: {city_phone}',
                'items' => [
                    [
                        'title' => 'Клиника {city_genitive}',
                        'body_html' => '<p>Приём в {city_accusative}</p>',
                    ],
                ],
                'keep_bool' => true,
                'keep_number' => 12,
            ],
        ]);

        $page = new Page([
            'title' => 'Главная {city}',
            'body_html' => '<p>{city_phone}</p>',
            'seo' => [
                'title' => 'SEO {city}',
                'description' => 'Описание {city_prepositional}',
            ],
        ]);
        $page->setRelation('blocks', new Collection([$block]));

        $resolvedPage = $page->withResolvedCitySeoVariables();
        $resolvedBlock = $resolvedPage->blocks->first();

        $this->assertSame('Главная Симферополь', $resolvedPage->title);
        $this->assertSame('Лечение в Симферополь', $resolvedBlock->title);
        $this->assertSame('<p>Запишитесь в в Симферополе</p>', $resolvedBlock->body_html);
        $this->assertSame('Телефон: +7 (978) 000-00-00', $resolvedBlock->payload['subtitle']);
        $this->assertSame('Клиника Симферополя', $resolvedBlock->payload['items'][0]['title']);
        $this->assertSame('<p>Приём в Симферополь</p>', $resolvedBlock->payload['items'][0]['body_html']);
        $this->assertTrue($resolvedBlock->payload['keep_bool']);
        $this->assertSame(12, $resolvedBlock->payload['keep_number']);
        $this->assertTrue($resolvedBlock->relationLoaded('page'));
        $this->assertSame($resolvedPage, $resolvedBlock->page);

        $this->assertSame('Лечение в {city}', $page->blocks->first()->title);
        $this->assertSame('Телефон: {city_phone}', $page->blocks->first()->payload['subtitle']);
    }
}
