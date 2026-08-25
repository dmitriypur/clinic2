<?php

namespace Tests\Feature\Blocks;

use App\Blocks\BlockRegistry;
use App\Enums\BlockType;
use Filament\Forms\Components\Component;
use Tests\TestCase;

class BlockDefinitionFormSchemaTest extends TestCase
{
    public function test_reception_steps_definition_preserves_its_form_state_paths(): void
    {
        $this->assertSame(
            ['payload.items', 'title', 'body_html'],
            $this->componentNames(BlockType::RECEPTION_STEPS),
        );
    }

    public function test_diagnostic_methods_definition_preserves_its_form_state_paths(): void
    {
        $this->assertSame(
            [
                'body_html',
                'default',
                'payload.cards_intro',
                'payload.items',
                'title',
                'body_html',
                'link',
                'media_collection',
                'image',
            ],
            $this->componentNames(BlockType::DIAGNOSTIC_METHODS),
        );
    }

    public function test_treatment_methods_definition_preserves_its_form_state_paths(): void
    {
        $this->assertSame(
            [
                'body_html',
                'payload.cards_intro',
                'payload.items',
                'title',
                'body_html',
                'media_collection',
                'image',
            ],
            $this->componentNames(BlockType::TREATMENT_METHODS),
        );
    }

    public function test_grid_contacts_definition_preserves_its_form_state_paths(): void
    {
        $this->assertSame(
            ['payload.image', 'payload.contacts', 'title', 'info'],
            $this->componentNames(BlockType::GRID_CONTACTS),
        );
    }

    private function componentNames(BlockType $type): array
    {
        $definition = app(BlockRegistry::class)->find($type);

        $this->assertNotNull($definition);

        return $this->collectComponentNames($definition->formSchema());
    }

    /**
     * @param  array<Component>  $components
     */
    private function collectComponentNames(array $components): array
    {
        $names = [];

        foreach ($components as $component) {
            if (method_exists($component, 'getName')) {
                $names[] = $component->getName();
            }

            if (method_exists($component, 'getChildComponents')) {
                $names = [
                    ...$names,
                    ...$this->collectComponentNames($component->getChildComponents()),
                ];
            }
        }

        return $names;
    }
}
