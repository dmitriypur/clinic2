<?php

declare(strict_types=1);

namespace App\Blocks\Generation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class BlockScaffolder
{
    private const ENUM_MARKER = '    // <block-generator-cases>';

    private const CONFIG_MARKER = '        // <block-generator-definitions>';

    public function __construct(private readonly Filesystem $files) {}

    /**
     * @return array<string>
     */
    public function generate(
        string $name,
        ?string $slug = null,
        ?string $enumName = null,
        ?string $rootPath = null,
    ): array {
        $rootPath = rtrim($rootPath ?? base_path(), DIRECTORY_SEPARATOR);
        $name = trim($name);
        $slug = trim($slug ?? Str::slug($name));
        $enumName = trim($enumName ?? Str::of($slug)->replace('-', '_')->upper()->toString());

        $this->validateNames($name, $slug, $enumName);

        $lockHandle = $this->acquireRepositoryLock($rootPath);

        try {

            $baseClass = Str::of($slug)->studly()->toString();
            $definitionClass = $baseClass.'Definition';
            $testClass = $baseClass.'BlockTest';
            $view = 'components.block.'.$slug;

            $paths = [
                'enum' => 'app/Enums/BlockType.php',
                'config' => 'config/block-definitions.php',
                'definition' => "app/Blocks/Definitions/{$definitionClass}.php",
                'view' => "resources/views/components/block/{$slug}.blade.php",
                'test' => "tests/Feature/Blocks/{$testClass}.php",
            ];

            $absolutePaths = collect($paths)
                ->map(fn (string $path) => $rootPath.DIRECTORY_SEPARATOR.$path)
                ->all();

            $stubPaths = [
                'definition' => $rootPath.'/stubs/block-definition.stub',
                'view' => $rootPath.'/stubs/block-view.stub',
                'test' => $rootPath.'/stubs/block-test.stub',
            ];

            $this->validateRequiredFiles($absolutePaths, $stubPaths);

            $enumSource = $this->files->get($absolutePaths['enum']);
            $configSource = $this->files->get($absolutePaths['config']);

            $this->validateSources($enumSource, $configSource, $enumName, $definitionClass);

            foreach (['definition', 'view', 'test'] as $destination) {
                if ($this->files->exists($absolutePaths[$destination])) {
                    throw new InvalidArgumentException("Destination already exists: {$paths[$destination]}");
                }
            }

            $nextValue = $this->nextEnumValue($enumSource);
            $enumSourceUpdated = str_replace(
                self::ENUM_MARKER,
                "    case {$enumName} = {$nextValue};\n\n".self::ENUM_MARKER,
                $enumSource,
            );
            $configSourceUpdated = str_replace(
                self::CONFIG_MARKER,
                "        App\\Blocks\\Definitions\\{$definitionClass}::class,\n".self::CONFIG_MARKER,
                $configSource,
            );

            $replacements = [
                '{{ class }}' => $definitionClass,
                '{{ enum }}' => $enumName,
                '{{ label }}' => var_export($name, true),
                '{{ view }}' => $view,
                '{{ slug }}' => $slug,
                '{{ test_class }}' => $testClass,
            ];

            $contents = [
                'enum' => $enumSourceUpdated,
                'config' => $configSourceUpdated,
                'definition' => strtr($this->files->get($stubPaths['definition']), $replacements),
                'view' => strtr($this->files->get($stubPaths['view']), $replacements),
                'test' => strtr($this->files->get($stubPaths['test']), $replacements),
            ];

            $backupFiles = $this->createBackups([
                $absolutePaths['enum'],
                $absolutePaths['config'],
            ]);
            $createdFiles = [];

            try {
                foreach (['enum', 'config', 'definition', 'view', 'test'] as $target) {
                    if (in_array($target, ['definition', 'view', 'test'], true)) {
                        $this->files->ensureDirectoryExists(dirname($absolutePaths[$target]));
                        $createdFiles[] = $absolutePaths[$target];
                    }

                    $this->write($absolutePaths[$target], $contents[$target]);
                }
            } catch (Throwable $exception) {
                $rollbackFailures = [];

                foreach ($backupFiles as $targetPath => $backupPath) {
                    $this->attemptRollback(
                        "{$targetPath}; backup: {$backupPath}",
                        fn () => $this->files->move($backupPath, $targetPath),
                        $rollbackFailures,
                    );
                }

                foreach ($createdFiles as $createdFile) {
                    if ($this->files->exists($createdFile)) {
                        $this->attemptRollback(
                            $createdFile,
                            fn () => $this->files->delete($createdFile),
                            $rollbackFailures,
                        );
                    }
                }

                if ($rollbackFailures !== []) {
                    throw new RuntimeException(
                        $exception->getMessage().' Rollback incomplete for: '.implode(', ', $rollbackFailures),
                        previous: $exception,
                    );
                }

                throw $exception;
            }

            foreach ($backupFiles as $backupPath) {
                if ($this->files->exists($backupPath)) {
                    $this->files->delete($backupPath);
                }
            }

            return array_values($paths);
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    /** @return resource */
    private function acquireRepositoryLock(string $rootPath)
    {
        $canonicalRoot = realpath($rootPath) ?: $rootPath;
        $lockPath = sys_get_temp_dir().'/zrenie-block-generator-'.sha1($canonicalRoot).'.lock';
        $lockHandle = fopen($lockPath, 'c+');

        if ($lockHandle === false) {
            throw new RuntimeException('Unable to create the block generator lock.');
        }

        if (! flock($lockHandle, LOCK_EX | LOCK_NB)) {
            fclose($lockHandle);

            throw new RuntimeException('Block generation is already running for this repository.');
        }

        return $lockHandle;
    }

    /**
     * @param  array<string>  $paths
     * @return array<string, string>
     */
    private function createBackups(array $paths): array
    {
        $backups = [];

        try {
            foreach ($paths as $path) {
                $backupPath = tempnam(dirname($path), '.block-generator-backup-');

                if ($backupPath === false || ! $this->files->copy($path, $backupPath)) {
                    if ($backupPath !== false) {
                        $backups[$path] = $backupPath;
                    }

                    throw new RuntimeException("Unable to back up block generator file: {$path}");
                }

                $backups[$path] = $backupPath;

                if (! chmod($backupPath, fileperms($path) & 0777)) {
                    throw new RuntimeException("Unable to preserve block generator backup permissions: {$path}");
                }
            }
        } catch (Throwable $exception) {
            foreach ($backups as $backupPath) {
                if ($this->files->exists($backupPath)) {
                    $this->files->delete($backupPath);
                }
            }

            throw $exception;
        }

        return $backups;
    }

    /** @param array<string> $failures */
    private function attemptRollback(string $path, callable $operation, array &$failures): void
    {
        try {
            if ($operation() === false) {
                throw new RuntimeException('Rollback operation returned false.');
            }
        } catch (Throwable $exception) {
            $failures[] = "{$path} ({$exception->getMessage()})";
        }
    }

    private function validateNames(string $name, string $slug, string $enumName): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('Block name cannot be empty.');
        }

        if ($slug === '' || ! preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArgumentException('Block slug must start with a lowercase Latin letter and contain only lowercase Latin letters, digits and hyphens.');
        }

        if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $enumName)) {
            throw new InvalidArgumentException('Block enum name must contain uppercase Latin letters, digits and underscores.');
        }
    }

    private function validateRequiredFiles(array $paths, array $stubPaths): void
    {
        foreach (['enum', 'config'] as $requiredPath) {
            if (! $this->files->isFile($paths[$requiredPath])) {
                throw new InvalidArgumentException("Required file not found: {$paths[$requiredPath]}");
            }
        }

        foreach ($stubPaths as $stubPath) {
            if (! $this->files->isFile($stubPath)) {
                throw new InvalidArgumentException("Required stub not found: {$stubPath}");
            }
        }
    }

    private function validateSources(
        string $enumSource,
        string $configSource,
        string $enumName,
        string $definitionClass,
    ): void {
        if (substr_count($enumSource, self::ENUM_MARKER) !== 1) {
            throw new InvalidArgumentException('BlockType generator marker not found or duplicated.');
        }

        if (substr_count($configSource, self::CONFIG_MARKER) !== 1) {
            throw new InvalidArgumentException('Block definition config marker not found or duplicated.');
        }

        if (preg_match('/\bcase\s+'.preg_quote($enumName, '/').'\s*=/', $enumSource)) {
            throw new InvalidArgumentException("Block enum case already exists: {$enumName}");
        }

        if (str_contains($configSource, "\\{$definitionClass}::class")) {
            throw new InvalidArgumentException("Block definition is already registered: {$definitionClass}");
        }
    }

    private function nextEnumValue(string $enumSource): int
    {
        preg_match_all('/\bcase\s+[A-Z][A-Z0-9_]*\s*=\s*(\d+)\s*;/', $enumSource, $matches);

        $values = array_map('intval', $matches[1] ?? []);

        if ($values === []) {
            throw new InvalidArgumentException('BlockType does not contain integer cases.');
        }

        return max($values) + 1;
    }

    private function write(string $path, string $contents): void
    {
        $temporaryPath = tempnam(dirname($path), '.block-generator-');

        if ($temporaryPath === false) {
            throw new RuntimeException("Unable to stage block generator file: {$path}");
        }

        try {
            $mode = $this->files->exists($path)
                ? (fileperms($path) & 0777)
                : (0666 & ~umask());

            if (! chmod($temporaryPath, $mode)) {
                throw new RuntimeException("Unable to preserve block generator file permissions: {$path}");
            }

            if ($this->files->put($temporaryPath, $contents) === false) {
                throw new RuntimeException("Unable to write block generator file: {$path}");
            }

            if (! rename($temporaryPath, $path)) {
                throw new RuntimeException("Unable to replace block generator file: {$path}");
            }
        } finally {
            if ($this->files->exists($temporaryPath)) {
                $this->files->delete($temporaryPath);
            }
        }
    }
}
