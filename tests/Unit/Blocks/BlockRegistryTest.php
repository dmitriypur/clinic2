<?php

namespace Tests\Unit\Blocks;

use App\Blocks\BlockRegistry;
use App\Blocks\Contracts\BlockDefinition;
use App\Blocks\Definitions\DiagnosticMethodsDefinition;
use App\Blocks\Definitions\ReceptionStepsDefinition;
use App\Blocks\Definitions\TreatmentMethodsDefinition;
use App\Enums\BlockType;
use App\Models\Block;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;
use Tests\TestCase;

if (interface_exists(BlockDefinition::class)) {
    final class ReceptionStepsTestDefinition implements BlockDefinition
    {
        public function type(): BlockType
        {
            return BlockType::RECEPTION_STEPS;
        }

        public function label(): string
        {
            return 'Этапы приема из реестра';
        }

        public function view(): string
        {
            return 'components.block.reception-steps';
        }

        public function formSchema(): array
        {
            return [];
        }

        public function viewData(Block $block): array
        {
            return ['resolved_block_id' => $block->getKey()];
        }
    }

    final class DuplicateReceptionStepsTestDefinition implements BlockDefinition
    {
        public function type(): BlockType
        {
            return BlockType::RECEPTION_STEPS;
        }

        public function label(): string
        {
            return 'Дубликат';
        }

        public function view(): string
        {
            return 'components.block.reception-steps';
        }

        public function formSchema(): array
        {
            return [];
        }

        public function viewData(Block $block): array
        {
            return [];
        }
    }
}

final class InvalidTestDefinition {}

class BlockRegistryTest extends TestCase
{
    public function test_it_resolves_registered_definitions_and_keeps_legacy_options(): void
    {
        $registry = new BlockRegistry(app(), [ReceptionStepsTestDefinition::class]);

        $definition = $registry->find(BlockType::RECEPTION_STEPS);

        $this->assertInstanceOf(ReceptionStepsTestDefinition::class, $definition);
        $this->assertTrue($registry->has(BlockType::RECEPTION_STEPS));
        $this->assertFalse($registry->has(BlockType::HTML));
        $this->assertNull($registry->find(BlockType::HTML));
        $this->assertSame('Этапы приема из реестра', $registry->label(BlockType::RECEPTION_STEPS));
        $this->assertNull($registry->label(BlockType::HTML));
        $this->assertSame('Этапы приема из реестра', $registry->options()[BlockType::RECEPTION_STEPS->value]);
        $this->assertSame('Текст', $registry->options()[BlockType::HTML->value]);
    }

    public function test_it_rejects_classes_that_do_not_implement_the_definition_contract(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(InvalidTestDefinition::class.' must implement BlockDefinition');

        new BlockRegistry(app(), [InvalidTestDefinition::class]);
    }

    public function test_it_rejects_duplicate_block_types(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate block definition for type '.BlockType::RECEPTION_STEPS->value);

        new BlockRegistry(app(), [
            ReceptionStepsTestDefinition::class,
            DuplicateReceptionStepsTestDefinition::class,
        ]);
    }

    public function test_application_registry_contains_the_three_pilot_definitions(): void
    {
        $registry = app(BlockRegistry::class);
        $expected = [
            BlockType::RECEPTION_STEPS->value => [
                ReceptionStepsDefinition::class,
                'Этапы приема',
                'components.block.reception-steps',
            ],
            BlockType::DIAGNOSTIC_METHODS->value => [
                DiagnosticMethodsDefinition::class,
                'Методы диагностики',
                'components.block.diagnostic-methods',
            ],
            BlockType::TREATMENT_METHODS->value => [
                TreatmentMethodsDefinition::class,
                'Методы лечения',
                'components.block.treatment-methods',
            ],
        ];

        foreach ($expected as $typeValue => [$class, $label, $view]) {
            $type = BlockType::from($typeValue);
            $definition = $registry->find($type);

            $this->assertInstanceOf($class, $definition);
            $this->assertSame($label, $definition->label());
            $this->assertSame($view, $definition->view());
            $this->assertTrue(View::exists($view));
        }

        $this->assertFalse($registry->has(BlockType::HTML));
        $this->assertSame('Текст', BlockType::HTML->getLabel());
    }

    public function test_block_type_label_prefers_a_registered_definition(): void
    {
        app()->instance(
            BlockRegistry::class,
            new BlockRegistry(app(), [ReceptionStepsTestDefinition::class]),
        );

        $this->assertSame('Этапы приема из реестра', BlockType::RECEPTION_STEPS->getLabel());
    }

    public function test_options_use_the_current_registry_instead_of_the_container_registry(): void
    {
        app()->instance(
            BlockRegistry::class,
            new BlockRegistry(app(), [ReceptionStepsTestDefinition::class]),
        );

        $localRegistry = new BlockRegistry(app(), []);

        $this->assertSame('Этапы приема', $localRegistry->options()[BlockType::RECEPTION_STEPS->value]);
    }
}
