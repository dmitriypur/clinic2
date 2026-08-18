<?php

namespace Tests\Feature;

use App\Blocks\Generation\BlockScaffolder;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MakeBlockCommandTest extends TestCase
{
    private Filesystem $files;

    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->fixtureRoot = sys_get_temp_dir().'/zrenie-block-generator-'.uniqid('', true);

        $this->createFixtureRoot($this->files);
    }

    protected function tearDown(): void
    {
        if (isset($this->files, $this->fixtureRoot) && $this->files->isDirectory($this->fixtureRoot)) {
            $this->files->deleteDirectory($this->fixtureRoot);
        }

        parent::tearDown();
    }

    public function test_scaffolder_creates_the_complete_block_structure(): void
    {
        $scaffolder = new BlockScaffolder($this->files);
        $enumModeBefore = fileperms($this->fixtureRoot.'/app/Enums/BlockType.php') & 0777;

        $changedFiles = $scaffolder->generate(
            'Этапы лечения',
            'treatment-steps',
            'TREATMENT_STEPS',
            $this->fixtureRoot,
        );

        $this->assertSame([
            'app/Enums/BlockType.php',
            'config/block-definitions.php',
            'app/Blocks/Definitions/TreatmentStepsDefinition.php',
            'resources/views/components/block/treatment-steps.blade.php',
            'tests/Feature/Blocks/TreatmentStepsBlockTest.php',
        ], $changedFiles);

        $enum = $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php');
        $config = $this->files->get($this->fixtureRoot.'/config/block-definitions.php');
        $definition = $this->files->get($this->fixtureRoot.'/app/Blocks/Definitions/TreatmentStepsDefinition.php');
        $view = $this->files->get($this->fixtureRoot.'/resources/views/components/block/treatment-steps.blade.php');
        $test = $this->files->get($this->fixtureRoot.'/tests/Feature/Blocks/TreatmentStepsBlockTest.php');

        $this->assertStringContainsString('case TREATMENT_STEPS = 9;', $enum);
        $this->assertStringContainsString('TreatmentStepsDefinition::class,', $config);
        $this->assertStringContainsString('final class TreatmentStepsDefinition', $definition);
        $this->assertStringContainsString('return BlockType::TREATMENT_STEPS;', $definition);
        $this->assertStringContainsString("return 'Этапы лечения';", $definition);
        $this->assertStringContainsString("return 'components.block.treatment-steps';", $definition);
        $this->assertStringContainsString('treatment-steps-block', $view);
        $this->assertStringContainsString('class TreatmentStepsBlockTest', $test);
        $this->assertStringContainsString('BlockType::TREATMENT_STEPS', $test);
        $this->assertSame($enumModeBefore, fileperms($this->fixtureRoot.'/app/Enums/BlockType.php') & 0777);
    }

    public function test_scaffolder_derives_transliterated_names_when_options_are_omitted(): void
    {
        $scaffolder = new BlockScaffolder($this->files);

        $changedFiles = $scaffolder->generate('Этапы лечения', rootPath: $this->fixtureRoot);

        $this->assertContains('app/Blocks/Definitions/EtapyLeceniiaDefinition.php', $changedFiles);
        $this->assertContains('resources/views/components/block/etapy-leceniia.blade.php', $changedFiles);
        $this->assertStringContainsString(
            'case ETAPY_LECENIIA = 9;',
            $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php'),
        );
    }

    public function test_scaffolder_rejects_a_conflict_before_mutating_registry_files(): void
    {
        $definitionPath = $this->fixtureRoot.'/app/Blocks/Definitions/TreatmentStepsDefinition.php';
        $this->files->ensureDirectoryExists(dirname($definitionPath));
        $this->files->put($definitionPath, 'existing definition');

        $enumBefore = $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php');
        $configBefore = $this->files->get($this->fixtureRoot.'/config/block-definitions.php');

        try {
            (new BlockScaffolder($this->files))->generate(
                'Этапы лечения',
                'treatment-steps',
                'TREATMENT_STEPS',
                $this->fixtureRoot,
            );

            $this->fail('Expected a destination conflict.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('already exists', $exception->getMessage());
        }

        $this->assertSame($enumBefore, $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php'));
        $this->assertSame($configBefore, $this->files->get($this->fixtureRoot.'/config/block-definitions.php'));
        $this->assertSame('existing definition', $this->files->get($definitionPath));
    }

    public function test_scaffolder_rejects_a_slug_that_would_create_an_invalid_php_class(): void
    {
        $enumBefore = $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php');
        $configBefore = $this->files->get($this->fixtureRoot.'/config/block-definitions.php');

        try {
            (new BlockScaffolder($this->files))->generate(
                'Лечение 123',
                '123-treatment',
                'TREATMENT_123',
                $this->fixtureRoot,
            );

            $this->fail('Expected an invalid slug error.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('must start with a lowercase Latin letter', $exception->getMessage());
        }

        $this->assertSame($enumBefore, $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php'));
        $this->assertSame($configBefore, $this->files->get($this->fixtureRoot.'/config/block-definitions.php'));
    }

    public function test_scaffolder_rejects_missing_markers_before_writing(): void
    {
        $enumPath = $this->fixtureRoot.'/app/Enums/BlockType.php';
        $enumBefore = str_replace('    // <block-generator-cases>', '', $this->files->get($enumPath));
        $this->files->put($enumPath, $enumBefore);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('BlockType generator marker not found');

        (new BlockScaffolder($this->files))->generate(
            'Этапы лечения',
            'treatment-steps',
            'TREATMENT_STEPS',
            $this->fixtureRoot,
        );
    }

    public function test_scaffolder_restores_original_files_when_a_write_fails(): void
    {
        $enumBefore = $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php');
        $configBefore = $this->files->get($this->fixtureRoot.'/config/block-definitions.php');
        $failingFiles = new FailOnceBlockGeneratorFilesystem(2);

        try {
            (new BlockScaffolder($failingFiles))->generate(
                'Этапы лечения',
                'treatment-steps',
                'TREATMENT_STEPS',
                $this->fixtureRoot,
            );

            $this->fail('Expected the injected write failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected block generator write failure.', $exception->getMessage());
        }

        $this->assertSame($enumBefore, $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php'));
        $this->assertSame($configBefore, $this->files->get($this->fixtureRoot.'/config/block-definitions.php'));
        $this->assertFileDoesNotExist($this->fixtureRoot.'/app/Blocks/Definitions/TreatmentStepsDefinition.php');
        $this->assertFileDoesNotExist($this->fixtureRoot.'/resources/views/components/block/treatment-steps.blade.php');
        $this->assertFileDoesNotExist($this->fixtureRoot.'/tests/Feature/Blocks/TreatmentStepsBlockTest.php');
    }

    public function test_scaffolder_refuses_to_run_while_the_same_repository_is_locked(): void
    {
        $enumBefore = $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php');
        $configBefore = $this->files->get($this->fixtureRoot.'/config/block-definitions.php');
        $lockPath = sys_get_temp_dir().'/zrenie-block-generator-'.sha1((string) realpath($this->fixtureRoot)).'.lock';
        $lockHandle = fopen($lockPath, 'c+');

        $this->assertIsResource($lockHandle);
        $this->assertTrue(flock($lockHandle, LOCK_EX | LOCK_NB));

        try {
            (new BlockScaffolder($this->files))->generate(
                'Этапы лечения',
                'treatment-steps',
                'TREATMENT_STEPS',
                $this->fixtureRoot,
            );

            $this->fail('Expected a repository lock conflict.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already running', $exception->getMessage());
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            $this->files->delete($lockPath);
        }

        $this->assertSame($enumBefore, $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php'));
        $this->assertSame($configBefore, $this->files->get($this->fixtureRoot.'/config/block-definitions.php'));
    }

    public function test_scaffolder_rolls_back_all_files_when_the_last_write_fails(): void
    {
        $enumBefore = $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php');
        $configBefore = $this->files->get($this->fixtureRoot.'/config/block-definitions.php');
        $failingFiles = new FailOnceBlockGeneratorFilesystem(5);

        try {
            (new BlockScaffolder($failingFiles))->generate(
                'Этапы лечения',
                'treatment-steps',
                'TREATMENT_STEPS',
                $this->fixtureRoot,
            );

            $this->fail('Expected the injected write failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected block generator write failure.', $exception->getMessage());
        }

        $this->assertSame($enumBefore, $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php'));
        $this->assertSame($configBefore, $this->files->get($this->fixtureRoot.'/config/block-definitions.php'));
        $this->assertFileDoesNotExist($this->fixtureRoot.'/app/Blocks/Definitions/TreatmentStepsDefinition.php');
        $this->assertFileDoesNotExist($this->fixtureRoot.'/resources/views/components/block/treatment-steps.blade.php');
        $this->assertFileDoesNotExist($this->fixtureRoot.'/tests/Feature/Blocks/TreatmentStepsBlockTest.php');
    }

    public function test_scaffolder_restores_registry_from_backups_without_relying_on_more_writes(): void
    {
        $enumBefore = $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php');
        $configBefore = $this->files->get($this->fixtureRoot.'/config/block-definitions.php');
        $failingFiles = new FailOnBlockGeneratorWritesFilesystem([
            5 => 'Original write failure.',
            6 => 'Rollback write failure.',
        ]);

        try {
            (new BlockScaffolder($failingFiles))->generate(
                'Этапы лечения',
                'treatment-steps',
                'TREATMENT_STEPS',
                $this->fixtureRoot,
            );

            $this->fail('Expected the original write failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Original write failure.', $exception->getMessage());
        }

        $this->assertSame($enumBefore, $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php'));
        $this->assertSame($configBefore, $this->files->get($this->fixtureRoot.'/config/block-definitions.php'));
        $this->assertFileDoesNotExist($this->fixtureRoot.'/app/Blocks/Definitions/TreatmentStepsDefinition.php');
        $this->assertFileDoesNotExist($this->fixtureRoot.'/resources/views/components/block/treatment-steps.blade.php');
        $this->assertFileDoesNotExist($this->fixtureRoot.'/tests/Feature/Blocks/TreatmentStepsBlockTest.php');
    }

    public function test_scaffolder_reports_the_paths_that_could_not_be_restored(): void
    {
        $configBefore = $this->files->get($this->fixtureRoot.'/config/block-definitions.php');

        try {
            (new BlockScaffolder(new FailingBlockGeneratorRestoreFilesystem))->generate(
                'Этапы лечения',
                'treatment-steps',
                'TREATMENT_STEPS',
                $this->fixtureRoot,
            );

            $this->fail('Expected the rollback failure report.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Original write failure.', $exception->getMessage());
            $this->assertStringContainsString('Rollback incomplete for:', $exception->getMessage());
            $this->assertStringContainsString('app/Enums/BlockType.php', $exception->getMessage());
            $this->assertSame('Original write failure.', $exception->getPrevious()?->getMessage());
        }

        $this->assertSame($configBefore, $this->files->get($this->fixtureRoot.'/config/block-definitions.php'));
        $this->assertFileDoesNotExist($this->fixtureRoot.'/app/Blocks/Definitions/TreatmentStepsDefinition.php');
        $this->assertFileDoesNotExist($this->fixtureRoot.'/resources/views/components/block/treatment-steps.blade.php');
        $this->assertFileDoesNotExist($this->fixtureRoot.'/tests/Feature/Blocks/TreatmentStepsBlockTest.php');
    }

    public function test_scaffolder_never_exposes_a_partially_written_registry_file(): void
    {
        $enumBefore = $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php');
        $configBefore = $this->files->get($this->fixtureRoot.'/config/block-definitions.php');

        try {
            (new BlockScaffolder(new PartialThenFailingBlockGeneratorFilesystem))->generate(
                'Этапы лечения',
                'treatment-steps',
                'TREATMENT_STEPS',
                $this->fixtureRoot,
            );

            $this->fail('Expected the partial write failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Unable to write block generator file', $exception->getMessage());
        }

        $this->assertSame($enumBefore, $this->files->get($this->fixtureRoot.'/app/Enums/BlockType.php'));
        $this->assertSame($configBefore, $this->files->get($this->fixtureRoot.'/config/block-definitions.php'));
    }

    public function test_make_block_command_reports_generated_files(): void
    {
        $scaffolder = Mockery::mock(BlockScaffolder::class);
        $scaffolder->shouldReceive('generate')
            ->once()
            ->with('Этапы лечения', 'treatment-steps', 'TREATMENT_STEPS', null)
            ->andReturn([
                'app/Enums/BlockType.php',
                'config/block-definitions.php',
                'app/Blocks/Definitions/TreatmentStepsDefinition.php',
                'resources/views/components/block/treatment-steps.blade.php',
                'tests/Feature/Blocks/TreatmentStepsBlockTest.php',
            ]);
        app()->instance(BlockScaffolder::class, $scaffolder);

        $this->artisan('make:block', [
            'name' => 'Этапы лечения',
            '--slug' => 'treatment-steps',
            '--type' => 'TREATMENT_STEPS',
        ])
            ->expectsOutputToContain('Блок создан:')
            ->expectsOutputToContain('app/Blocks/Definitions/TreatmentStepsDefinition.php')
            ->expectsOutputToContain('Дальше: заполните formSchema(), Blade-шаблон и тест.')
            ->assertSuccessful();
    }

    private function createFixtureRoot(Filesystem $files): void
    {
        $files->ensureDirectoryExists($this->fixtureRoot.'/app/Enums');
        $files->ensureDirectoryExists($this->fixtureRoot.'/config');
        $files->ensureDirectoryExists($this->fixtureRoot.'/stubs');

        $files->put($this->fixtureRoot.'/app/Enums/BlockType.php', <<<'PHP'
<?php

namespace App\Enums;

enum BlockType: int
{
    case FIRST = 3;
    case LAST = 8;

    // <block-generator-cases>
}
PHP);

        $files->put($this->fixtureRoot.'/config/block-definitions.php', <<<'PHP'
<?php

return [
    'definitions' => [
        // <block-generator-definitions>
    ],
];
PHP);

        $files->put($this->fixtureRoot.'/stubs/block-definition.stub', <<<'STUB'
<?php

namespace App\Blocks\Definitions;

final class {{ class }}
{
    public function type(): BlockType { return BlockType::{{ enum }}; }
    public function label(): string { return {{ label }}; }
    public function view(): string { return '{{ view }}'; }
}
STUB);

        $files->put($this->fixtureRoot.'/stubs/block-view.stub', <<<'STUB'
<div class="{{ slug }}-block">{{ $block->title }}</div>
STUB);

        $files->put($this->fixtureRoot.'/stubs/block-test.stub', <<<'STUB'
<?php

class {{ test_class }} extends TestCase
{
    private BlockType $type = BlockType::{{ enum }};
}
STUB);
    }
}

final class FailOnceBlockGeneratorFilesystem extends Filesystem
{
    private int $writeCount = 0;

    public function __construct(private readonly int $failureWriteNumber) {}

    public function put($path, $contents, $lock = false)
    {
        $this->writeCount++;

        if ($this->writeCount === $this->failureWriteNumber) {
            throw new RuntimeException('Injected block generator write failure.');
        }

        return parent::put($path, $contents, $lock);
    }
}

final class FailOnBlockGeneratorWritesFilesystem extends Filesystem
{
    private int $writeCount = 0;

    /** @param array<int, string> $failures */
    public function __construct(private readonly array $failures) {}

    public function put($path, $contents, $lock = false)
    {
        $this->writeCount++;

        if (isset($this->failures[$this->writeCount])) {
            throw new RuntimeException($this->failures[$this->writeCount]);
        }

        return parent::put($path, $contents, $lock);
    }
}

final class PartialThenFailingBlockGeneratorFilesystem extends Filesystem
{
    private int $writeCount = 0;

    public function put($path, $contents, $lock = false)
    {
        $this->writeCount++;

        if ($this->writeCount === 1) {
            parent::put($path, 'partial contents', $lock);

            return false;
        }

        if ($this->writeCount === 2) {
            throw new RuntimeException('Rollback write failure.');
        }

        return parent::put($path, $contents, $lock);
    }
}

final class FailingBlockGeneratorRestoreFilesystem extends Filesystem
{
    private int $writeCount = 0;

    private int $moveCount = 0;

    public function put($path, $contents, $lock = false)
    {
        $this->writeCount++;

        if ($this->writeCount === 5) {
            throw new RuntimeException('Original write failure.');
        }

        return parent::put($path, $contents, $lock);
    }

    public function move($path, $target)
    {
        $this->moveCount++;

        if ($this->moveCount === 1) {
            return false;
        }

        return parent::move($path, $target);
    }
}
