<?php

declare(strict_types=1);

namespace App\Blocks;

use App\Blocks\Contracts\BlockDefinition;
use App\Enums\BlockType;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class BlockRegistry
{
    /** @var array<int, BlockDefinition> */
    private array $definitions = [];

    /**
     * @param  array<class-string>  $definitionClasses
     */
    public function __construct(Container $container, array $definitionClasses)
    {
        foreach ($definitionClasses as $definitionClass) {
            $definition = $container->make($definitionClass);

            if (! $definition instanceof BlockDefinition) {
                throw new InvalidArgumentException("{$definitionClass} must implement BlockDefinition");
            }

            $key = $definition->type()->value;

            if (isset($this->definitions[$key])) {
                throw new InvalidArgumentException("Duplicate block definition for type {$key}");
            }

            $this->definitions[$key] = $definition;
        }
    }

    public function find(BlockType $type): ?BlockDefinition
    {
        return $this->definitions[$type->value] ?? null;
    }

    public function has(BlockType $type): bool
    {
        return $this->find($type) !== null;
    }

    public function label(BlockType $type): ?string
    {
        return $this->find($type)?->label();
    }

    public function options(): array
    {
        return collect(BlockType::cases())
            ->mapWithKeys(fn (BlockType $type) => [
                $type->value => $this->label($type) ?? $type->legacyLabel(),
            ])
            ->sort()
            ->all();
    }
}
