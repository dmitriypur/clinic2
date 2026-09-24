<?php

namespace Tests\Unit;

use App\Models\Block;
use App\Models\City;
use Tests\TestCase;

class CityDetailsItemsTest extends TestCase
{
    /** @test */
    public function null_empty_and_blank_details_produce_no_items(): void
    {
        foreach ([null, [], [[]], [null]] as $details) {
            $city = new City(['details' => $details]);

            $this->assertSame([], $city->details_items);
        }

        $city = new City(['details' => [[], ['name' => 'Клиника']]]);

        $this->assertSame([[
            'name' => 'Клиника',
            'rows' => [],
            'columns' => [],
        ]], $city->details_items);
    }

    /** @test */
    public function name_only_details_keep_the_name_without_rows_or_columns(): void
    {
        $city = new City(['details' => [['name' => 'Клиника']]]);

        $this->assertSame([
            [
                'name' => 'Клиника',
                'rows' => [],
                'columns' => [],
            ],
        ], $city->details_items);
    }

    /** @test */
    public function filled_details_keep_field_order_labels_and_column_sizes(): void
    {
        $cases = [
            [
                ['ogrn' => '123'],
                [['label' => 'ОГРН', 'value' => '123']],
                [[0]],
            ],
            [
                ['ogrn' => '123', 'director' => 'Иванов'],
                [
                    ['label' => 'Директор', 'value' => 'Иванов'],
                    ['label' => 'ОГРН', 'value' => '123'],
                ],
                [[0], [1]],
            ],
            [
                ['inn' => '456', 'legal_address' => 'Адрес', 'director' => 'Иванов'],
                [
                    ['label' => 'Директор', 'value' => 'Иванов'],
                    ['label' => 'Юридический адрес', 'value' => 'Адрес'],
                    ['label' => 'ИНН/КПП', 'value' => '456'],
                ],
                [[0, 1], [2]],
            ],
            [
                [
                    'fullname' => 'ООО Клиника',
                    'director' => 'Иванов',
                    'legal_address' => 'Юридический адрес',
                    'postal_address' => 'Почтовый адрес',
                    'ogrn' => '123',
                    'inn' => '456',
                ],
                [
                    ['label' => 'Полное наименование организации', 'value' => 'ООО Клиника'],
                    ['label' => 'Директор', 'value' => 'Иванов'],
                    ['label' => 'Юридический адрес', 'value' => 'Юридический адрес'],
                    ['label' => 'Почтовый адрес', 'value' => 'Почтовый адрес'],
                    ['label' => 'ОГРН', 'value' => '123'],
                    ['label' => 'ИНН/КПП', 'value' => '456'],
                ],
                [[0, 1, 2], [3, 4, 5]],
            ],
        ];

        foreach ($cases as [$fields, $rows, $columnIndexes]) {
            $city = new City(['details' => [$fields]]);
            $item = $city->details_items[0];

            $this->assertNull($item['name']);
            $this->assertSame($rows, $item['rows']);
            $this->assertSame(
                array_map(fn (array $indexes) => array_map(fn (int $index) => $rows[$index], $indexes), $columnIndexes),
                $item['columns']
            );
        }
    }

    /** @test */
    public function name_only_details_render_in_related_blade_components(): void
    {
        $city = new City(['details' => [['name' => 'Клиника']]]);
        $block = new Block(['settings' => ['title_hidden' => true], 'payload' => []]);

        $copyright = view('components.copyright', ['currentCity' => $city])->render();
        $details = view('components.block.details', [
            'currentCity' => $city,
            'block' => $block,
        ])->render();

        $this->assertStringContainsString('Клиника', $copyright);
        $this->assertStringContainsString('<h3', $details);
        $this->assertStringContainsString('Клиника', $details);
    }

    /** @test */
    public function empty_details_render_in_related_blade_components(): void
    {
        $block = new Block(['settings' => ['title_hidden' => true], 'payload' => []]);

        foreach ([null, [], [[]]] as $source) {
            $city = new City(['details' => $source]);

            $copyright = view('components.copyright', ['currentCity' => $city])->render();
            $details = view('components.block.details', [
                'currentCity' => $city,
                'block' => $block,
            ])->render();

            $this->assertStringContainsString('©', $copyright);
            $this->assertStringNotContainsString('<h3', $details);
            $this->assertStringContainsString('НЕ ОКАЗЫВАЕТ УСЛУГ', $details);
        }
    }

    /** @test */
    public function filled_details_still_render_the_organization_and_rows(): void
    {
        $city = new City(['details' => [[
            'name' => 'Клиника',
            'director' => 'Иванов',
        ]]]);
        $block = new Block(['settings' => ['title_hidden' => true], 'payload' => []]);

        $copyright = view('components.copyright', ['currentCity' => $city])->render();
        $details = view('components.block.details', [
            'currentCity' => $city,
            'block' => $block,
        ])->render();

        $this->assertStringContainsString('Клиника', $copyright);
        $this->assertStringContainsString('Клиника', $details);
        $this->assertStringContainsString('Директор', $details);
        $this->assertStringContainsString('Иванов', $details);
    }
}
