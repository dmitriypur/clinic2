# Block Registry Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Добавить обратно совместимый реестр типов блоков, перенести в него три пилотных блока и создать Artisan-генератор новых definition/view/test файлов без изменения базы данных.

**Architecture:** `BlockRegistry` разрешает `BlockType` в небольшой самостоятельный `BlockDefinition`. Перенесённые типы получают форму и view из definition, а остальные продолжают использовать существующие условные поля и Blade `@switch`; генератор создаёт enum case, definition, view, test и запись конфигурационного реестра атомарно.

**Tech Stack:** PHP 8.1/8.2, Laravel 10.49, Blade, Filament 3.3, PHPUnit 10, Spatie Media Library.

**Spec:** `docs/superpowers/specs/2026-08-18-block-registry-design.md`

## Global Constraints

- Не добавлять migrations и не изменять структуру таблицы `blocks`.
- Не менять integer values существующих `BlockType`.
- Не переименовывать существующие `payload`, `settings` и media collection keys.
- Не переносить model accessors и media conversions на этом этапе.
- Сохранить legacy fallback для всех неперенесённых типов.
- Не обновлять `docs/APP_CONTEXT.md` без прямой просьбы пользователя.
- Не включать `.DS_Store` и другие посторонние изменения в коммиты.
- Использовать TDD: красный тест, минимальная реализация, зелёный тест.

## File Map

Новые production-файлы:

- `app/Blocks/Contracts/BlockDefinition.php` — минимальный контракт одного типа блока.
- `app/Blocks/AbstractBlockDefinition.php` — пустая реализация дополнительных view data.
- `app/Blocks/BlockRegistry.php` — валидация и поиск definitions по `BlockType`.
- `app/Blocks/Definitions/ReceptionStepsDefinition.php` — форма и view `RECEPTION_STEPS`.
- `app/Blocks/Definitions/DiagnosticMethodsDefinition.php` — форма и view `DIAGNOSTIC_METHODS`.
- `app/Blocks/Definitions/TreatmentMethodsDefinition.php` — форма и view `TREATMENT_METHODS`.
- `app/Blocks/Generation/BlockScaffolder.php` — безопасная подготовка и запись файлов нового типа.
- `app/Console/Commands/MakeBlockCommand.php` — CLI-оболочка генератора.
- `config/block-definitions.php` — явный cache-friendly список definition classes.
- `stubs/block-definition.stub` — definition-заготовка.
- `stubs/block-view.stub` — Blade-заготовка.
- `stubs/block-test.stub` — feature-test заготовка.

Изменяемые production-файлы:

- `app/Providers/AppServiceProvider.php` — singleton binding реестра.
- `app/Enums/BlockType.php` — marker для генератора и label lookup для зарегистрированных типов.
- `app/Filament/Resources/BlockResource.php` — definition schema + legacy fallback.
- `resources/views/components/block.blade.php` — definition view + legacy `@switch` fallback.

Новые/изменяемые тесты:

- `tests/Unit/Blocks/BlockRegistryTest.php`.
- `tests/Feature/Blocks/BlockDefinitionFormSchemaTest.php`.
- `tests/Feature/MakeBlockCommandTest.php`.
- существующие `ReceptionStepsBlockTest`, `DiagnosticMethodsBlockTest`, `TreatmentMethodsBlockTest` используются как render regression tests.

---

### Task 1: BlockDefinition contract and registry

**Files:**

- Create: `app/Blocks/Contracts/BlockDefinition.php`
- Create: `app/Blocks/AbstractBlockDefinition.php`
- Create: `app/Blocks/BlockRegistry.php`
- Create: `config/block-definitions.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Unit/Blocks/BlockRegistryTest.php`

**Interfaces:**

- Produces: `BlockDefinition::type(): BlockType`
- Produces: `BlockDefinition::label(): string`
- Produces: `BlockDefinition::view(): string`
- Produces: `BlockDefinition::formSchema(): array`
- Produces: `BlockDefinition::viewData(Block $block): array`
- Produces: `BlockRegistry::find(BlockType $type): ?BlockDefinition`
- Produces: `BlockRegistry::has(BlockType $type): bool`
- Produces: `BlockRegistry::label(BlockType $type): ?string`
- Produces: `BlockRegistry::options(): array`

- [ ] **Step 1: Write failing registry tests**

Create a private fake definition in the test and assert lookup, label, options, unknown fallback, interface validation and duplicate-type rejection:

```php
final class FakeReceptionStepsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType { return BlockType::RECEPTION_STEPS; }
    public function label(): string { return 'Этапы приема'; }
    public function view(): string { return 'components.block.reception-steps'; }
    public function formSchema(): array { return []; }
}

$registry = new BlockRegistry(app(), [FakeReceptionStepsDefinition::class]);

$this->assertInstanceOf(FakeReceptionStepsDefinition::class, $registry->find(BlockType::RECEPTION_STEPS));
$this->assertTrue($registry->has(BlockType::RECEPTION_STEPS));
$this->assertNull($registry->find(BlockType::HTML));
$this->assertSame('Этапы приема', $registry->label(BlockType::RECEPTION_STEPS));
```

- [ ] **Step 2: Run the registry test and verify failure**

Run:

```bash
/Applications/MAMP/bin/php/php8.2.0/bin/php artisan test tests/Unit/Blocks/BlockRegistryTest.php
```

Expected: FAIL because `BlockRegistry` and `BlockDefinition` do not exist.

- [ ] **Step 3: Implement the contract and base definition**

Use this contract:

```php
interface BlockDefinition
{
    public function type(): BlockType;
    public function label(): string;
    public function view(): string;
    public function formSchema(): array;
    public function viewData(Block $block): array;
}
```

`AbstractBlockDefinition::viewData()` returns `[]`; it must not change or clone the model.

- [ ] **Step 4: Implement BlockRegistry validation and lookup**

Construct definitions through Laravel's container and index them by integer type value:

```php
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
```

`options()` iterates over all `BlockType::cases()` and uses the definition label when available, otherwise the current enum label. Preserve the existing label sorting behavior.

- [ ] **Step 5: Bind the registry and add empty config**

Create `config/block-definitions.php` with an empty `definitions` array and marker:

```php
return [
    'definitions' => [
        // <block-generator-definitions>
    ],
];
```

Bind the registry in `AppServiceProvider::register()`:

```php
$this->app->singleton(
    BlockRegistry::class,
    fn ($app) => new BlockRegistry($app, config('block-definitions.definitions', [])),
);
```

- [ ] **Step 6: Run focused tests**

Run the registry test again. Expected: PASS.

- [ ] **Step 7: Commit the registry foundation**

```bash
git add app/Blocks app/Providers/AppServiceProvider.php config/block-definitions.php tests/Unit/Blocks/BlockRegistryTest.php
git commit -m "refactor: add block definition registry"
```

---

### Task 2: Registry-aware labels and rendering with legacy fallback

**Files:**

- Modify: `app/Enums/BlockType.php`
- Create: `app/Blocks/Definitions/ReceptionStepsDefinition.php`
- Create: `app/Blocks/Definitions/DiagnosticMethodsDefinition.php`
- Create: `app/Blocks/Definitions/TreatmentMethodsDefinition.php`
- Modify: `config/block-definitions.php`
- Modify: `resources/views/components/block.blade.php`
- Modify: `tests/Unit/Blocks/BlockRegistryTest.php`
- Test: `tests/Feature/ReceptionStepsBlockTest.php`
- Test: `tests/Feature/DiagnosticMethodsBlockTest.php`
- Test: `tests/Feature/TreatmentMethodsBlockTest.php`

**Interfaces:**

- Consumes: Task 1 `BlockDefinition` and `BlockRegistry`.
- Produces: registry-backed label resolution for registered types.
- Produces: registry-backed view selection before the unchanged legacy `@switch`.

- [ ] **Step 1: Extend tests for production config coverage**

Assert that the application registry contains exactly the three pilot types, that their labels match the current Russian labels and that each configured view exists through `View::exists()`.

Also assert that `BlockType::HTML` remains unregistered and its label remains `Текст`.

- [ ] **Step 2: Run tests and verify failure**

Expected: FAIL because pilot definitions are not registered.

- [ ] **Step 3: Create the three pilot definitions**

Each definition extends `AbstractBlockDefinition`, returns its existing enum type, current label and current view path. Initially `formSchema()` returns an empty array; Task 3 fills it.

Example:

```php
final class ReceptionStepsDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType { return BlockType::RECEPTION_STEPS; }
    public function label(): string { return 'Этапы приема'; }
    public function view(): string { return 'components.block.reception-steps'; }
    public function formSchema(): array { return []; }
}
```

- [ ] **Step 4: Register pilot definitions**

Add all three class names above the config marker. Do not register any other block.

- [ ] **Step 5: Make BlockType labels registry-aware**

At the start of `getLabel()` return a registered definition label when available, then execute the current legacy match. Add a generator marker immediately before `getLabel()`:

```php
// <block-generator-cases>

public function getLabel(): string
{
    if ($label = app(BlockRegistry::class)->label($this)) {
        return $label;
    }

    return match ($this) {
        // existing cases stay unchanged
    };
}
```

- [ ] **Step 6: Add registry rendering before legacy switch**

Resolve the definition once inside the existing section and render it when present:

```blade
@php($blockDefinition = app(\App\Blocks\BlockRegistry::class)->find($block->type))

@if($blockDefinition)
    @include($blockDefinition->view(), $blockDefinition->viewData($block))
@else
    @switch($block->type)
        @case(\App\Enums\BlockType::MAIN_PAGE_STATIC_BLOCK)
            <x-block.main/>
            @break

        @case(\App\Enums\BlockType::TEXT_WITH_IMAGE)
            <div class="container">
                <x-block.text-with-image :block="$block"/>
            </div>
            @break
    @endswitch
@endif
```

The two cases shown above demonstrate that the existing case bodies stay byte-for-byte unchanged inside the wrapper. Keep every current case except `RECEPTION_STEPS`, `DIAGNOSTIC_METHODS` and `TREATMENT_METHODS`; remove only those three pilot cases. Blade includes inherit `$block`, `$page` and the wrapper context.

- [ ] **Step 7: Run render and registry tests**

Run:

```bash
/Applications/MAMP/bin/php/php8.2.0/bin/php artisan test tests/Unit/Blocks/BlockRegistryTest.php tests/Feature/ReceptionStepsBlockTest.php tests/Feature/DiagnosticMethodsBlockTest.php tests/Feature/TreatmentMethodsBlockTest.php
```

Expected: PASS with the same HTML assertions as baseline.

- [ ] **Step 8: Commit registry-backed rendering**

```bash
git add app/Enums/BlockType.php app/Blocks/Definitions config/block-definitions.php resources/views/components/block.blade.php tests/Unit/Blocks/BlockRegistryTest.php
git commit -m "refactor: render pilot blocks through registry"
```

---

### Task 3: Registry-aware Filament schemas for pilot blocks

**Files:**

- Modify: `app/Blocks/Definitions/ReceptionStepsDefinition.php`
- Modify: `app/Blocks/Definitions/DiagnosticMethodsDefinition.php`
- Modify: `app/Blocks/Definitions/TreatmentMethodsDefinition.php`
- Modify: `app/Filament/Resources/BlockResource.php`
- Create: `tests/Feature/Blocks/BlockDefinitionFormSchemaTest.php`

**Interfaces:**

- Consumes: `BlockDefinition::formSchema(): array` and `BlockRegistry::find()`.
- Produces: pilot schemas with unchanged state paths.
- Preserves: all legacy fields for unregistered types.

- [ ] **Step 1: Write failing form-schema tests**

Resolve each definition from the container and recursively inspect component names, descending into Section and Repeater child component containers. Assert these exact state paths:

```php
$this->assertSame(
    ['payload.items'],
    $this->topLevelComponentNames(BlockType::RECEPTION_STEPS),
);

$this->assertSame(
    ['body_html', 'default', 'payload.cards_intro', 'payload.items'],
    $this->topLevelComponentNames(BlockType::DIAGNOSTIC_METHODS),
);

$this->assertSame(
    ['body_html', 'payload.cards_intro', 'payload.items'],
    $this->topLevelComponentNames(BlockType::TREATMENT_METHODS),
);
```

Inspect the repeater child components and assert that diagnostic items contain `title`, `body_html`, `link`, `media_collection`, `image`; treatment contains `title`, `body_html`, `media_collection`, `image`; reception contains `title`, `body_html`.

- [ ] **Step 2: Run the schema test and verify failure**

Expected: FAIL because pilot `formSchema()` methods are empty.

- [ ] **Step 3: Move the reception schema unchanged**

Move the existing `payload.items` Section/Repeater component into `ReceptionStepsDefinition::formSchema()`. Preserve labels, `minItems(1)`, `required()` and both child field definitions exactly; remove only the old reception-specific Section from `BlockResource`.

- [ ] **Step 4: Move the diagnostic schema unchanged**

Return these components in the existing order:

```php
Forms\Components\RichEditor::make('body_html')->label('Текст')->columnSpanFull();
SpatieMediaLibraryFileUpload::make('default')->label('Изображение')->imageEditor()->responsiveImages()->openable();
Forms\Components\Textarea::make('payload.cards_intro')->label('Подзаголовок перед карточками')->columnSpanFull();
Forms\Components\Section::make([
    Forms\Components\Repeater::make('payload.items')
        ->label('Методы диагностики')
        ->schema([
            Forms\Components\TextInput::make('title')
                ->label('Заголовок')
                ->columnSpanFull()
                ->required(),
            Forms\Components\RichEditor::make('body_html')
                ->label('Текст')
                ->columnSpanFull()
                ->required(),
            Forms\Components\TextInput::make('link')
                ->label('Ссылка')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('media_collection')
                ->columnSpanFull()
                ->hiddenLabel()
                ->default(fn (Forms\Get $get) => $get('media_collection') ?? Str::uuid()->toString())
                ->reactive()
                ->extraAttributes(['class' => 'hidden']),
            SpatieMediaLibraryFileUpload::make('image')
                ->collection(fn (Forms\Get $get) => $get('media_collection'))
                ->label('Мини-изображение')
                ->imageEditor()
                ->responsiveImages(),
        ])
        ->minItems(1)
        ->required(),
]);
```

Remove `DIAGNOSTIC_METHODS` only from the relevant legacy visibility arrays and remove its dedicated repeater Section.

- [ ] **Step 5: Move the treatment schema unchanged**

Move `body_html`, `payload.cards_intro` and the existing treatment repeater. Preserve the current absence of a `link` input in the treatment admin schema, as adding it would be a behavior change outside this refactor.

Remove `TREATMENT_METHODS` only from the relevant legacy visibility arrays and remove its dedicated repeater Section.

- [ ] **Step 6: Add the dynamic definition schema container**

Immediately after the reactive type select, add a full-width Group whose schema closure safely resolves the selected enum:

```php
Forms\Components\Group::make()
    ->schema(function (Forms\Get $get): array {
        $value = $get('type');

        if ($value === null || $value === '') {
            return [];
        }

        $type = BlockType::tryFrom((int) $value);

        return $type
            ? app(BlockRegistry::class)->find($type)?->formSchema() ?? []
            : [];
    })
    ->columnSpanFull(),
```

Use `BlockRegistry::options()` for both BlockResource type option lists; legacy labels remain unchanged.

- [ ] **Step 7: Run schema and render regressions**

Run the schema test plus the three existing render tests. Expected: PASS.

- [ ] **Step 8: Run syntax/style checks for changed PHP files**

Run `php -l` on each changed PHP file and Pint only on explicitly changed PHP paths. Expected: no syntax errors and no uncommitted formatting outside scope.

- [ ] **Step 9: Commit pilot form extraction**

```bash
git add app/Blocks/Definitions app/Filament/Resources/BlockResource.php tests/Feature/Blocks/BlockDefinitionFormSchemaTest.php
git commit -m "refactor: move pilot block forms to definitions"
```

---

### Task 4: Atomic make:block generator

**Files:**

- Create: `app/Blocks/Generation/BlockScaffolder.php`
- Create: `app/Console/Commands/MakeBlockCommand.php`
- Create: `stubs/block-definition.stub`
- Create: `stubs/block-view.stub`
- Create: `stubs/block-test.stub`
- Create: `tests/Feature/MakeBlockCommandTest.php`

**Interfaces:**

- Produces: `BlockScaffolder::generate(string $name, ?string $slug = null, ?string $enumName = null, ?string $rootPath = null): array`
- Produces command: `make:block {name} {--slug=} {--type=}`
- Consumes markers: `<block-generator-cases>` and `<block-generator-definitions>`.

- [ ] **Step 1: Write failing scaffolder tests in a temporary fixture root**

Create a temporary root under `storage/framework/testing` containing copies of the enum/config files and the real stubs. Call `generate('Этапы лечения', null, null, $temporaryRoot)` and assert:

- enum `TREATMENT_STEPS` is inserted with `max(BlockType::cases()->value) + 1`;
- config contains `TreatmentStepsDefinition::class` above the marker;
- definition, Blade and test files exist;
- generated definition source contains label `Этапы лечения`, slug-based view and enum type methods;
- generated Blade contains a stable `treatment-steps-block` class;
- returned file list contains all five modified/created paths.

- [ ] **Step 2: Add conflict and rollback tests**

Assert that:

- an existing destination aborts before mutation;
- an existing enum name aborts before mutation;
- missing markers abort before mutation;
- if a write throws after the first mutation, original enum/config contents are restored and newly created files are removed.

- [ ] **Step 3: Run the generator tests and verify failure**

Expected: FAIL because `BlockScaffolder` does not exist.

- [ ] **Step 4: Implement naming and preflight**

Derive defaults with Laravel `Str`:

```php
$slug = $slug ?: Str::slug($name);
$enumName = $enumName ?: Str::of($slug)->replace('-', '_')->upper()->toString();
$class = Str::of($slug)->studly()->append('Definition')->toString();
$nextValue = collect(BlockType::cases())->max(fn (BlockType $type) => $type->value) + 1;
```

Validate non-empty slug, `/^[A-Z][A-Z0-9_]*$/` enum name, class/file conflicts and both markers before any write.

- [ ] **Step 5: Implement atomic writes**

Render all target contents in memory first. Store original enum/config contents. Wrap writes in `try/catch`; on failure restore both originals and delete only files created by this invocation, then rethrow.

Do not overwrite an existing definition, view or test.

- [ ] **Step 6: Create exact stubs**

The definition stub extends `AbstractBlockDefinition`, returns the generated enum, Russian label, `components.block.<slug>` view and an empty schema.

The Blade stub renders the block title inside `.container` and a stable `<slug>-block` wrapper.

The test stub constructs `Block` with the generated enum and asserts the wrapper class and title through the existing `<x-block>` component.

- [ ] **Step 7: Add the Artisan command**

Implement:

```php
protected $signature = 'make:block
    {name : Название блока в админке}
    {--slug= : Blade slug, например treatment-steps}
    {--type= : Enum case, например TREATMENT_STEPS}';
```

Call `BlockScaffolder`, print each changed file and return `Command::SUCCESS`. Convert validation/conflict exceptions into one clear error and `Command::FAILURE` without a stack trace.

- [ ] **Step 8: Test the command registration and output**

Bind a test scaffolder response, run `$this->artisan('make:block', ['name' => 'Этапы лечения'])`, assert successful status and generated paths in output.

- [ ] **Step 9: Run generator and registry tests**

Expected: all PASS; confirm the real repository was not modified by generator tests.

- [ ] **Step 10: Commit the generator**

```bash
git add app/Blocks/Generation app/Console/Commands/MakeBlockCommand.php stubs tests/Feature/MakeBlockCommandTest.php
git commit -m "feat: add block type generator"
```

---

### Task 5: Compatibility verification and handoff

**Files:**

- Modify only files already in scope if verification finds a defect.
- Do not modify `docs/APP_CONTEXT.md`.

**Interfaces:**

- Consumes all previous tasks.
- Produces a verified first-stage registry implementation with no database migration.

- [ ] **Step 1: Run the focused block suite**

```bash
/Applications/MAMP/bin/php/php8.2.0/bin/php artisan test tests/Unit/Blocks/BlockRegistryTest.php tests/Feature/Blocks/BlockDefinitionFormSchemaTest.php tests/Feature/MakeBlockCommandTest.php tests/Feature/ReceptionStepsBlockTest.php tests/Feature/DiagnosticMethodsBlockTest.php tests/Feature/TreatmentMethodsBlockTest.php tests/Feature/ArticleNavigationTest.php
```

Expected: all tests pass.

- [ ] **Step 2: Verify database compatibility statically**

Confirm with `git diff --name-only main...HEAD` that no file under `database/migrations` changed. Confirm the enum diff does not change existing case values.

- [ ] **Step 3: Verify registry coverage and legacy fallback**

Run a focused test asserting three registered cases and at least one unregistered legacy case. Render `HTML` and one pilot block through `<x-block>` and confirm both paths succeed.

- [ ] **Step 4: Run PHP syntax and formatting checks**

Run `php -l` for all changed PHP files and `vendor/bin/pint --test` restricted to changed PHP paths. Fix only scoped formatting.

- [ ] **Step 5: Inspect the final diff and worktree**

Confirm:

- `.DS_Store` remains unstaged and unchanged by this work;
- no payload key or media collection was renamed;
- no migration exists;
- only the three pilot cases were removed from the legacy switch and legacy form sections;
- no unrelated code changed.

- [ ] **Step 6: Run final focused tests after any formatting fix**

Expected: all PASS with the same assertion counts or higher.

- [ ] **Step 7: Commit verification-only fixes if needed**

If verification required code changes, commit only those paths:

```bash
git add app/Blocks app/Console/Commands/MakeBlockCommand.php app/Enums/BlockType.php app/Filament/Resources/BlockResource.php app/Providers/AppServiceProvider.php config/block-definitions.php resources/views/components/block.blade.php stubs tests/Unit/Blocks tests/Feature/Blocks tests/Feature/MakeBlockCommandTest.php
git commit -m "test: verify block registry compatibility"
```

If no fixes were required, do not create an empty commit.
