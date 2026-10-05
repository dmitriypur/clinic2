<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ZipArchive;

class BackupRetentionTest extends TestCase
{
    private string $temporaryStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryStorage = sys_get_temp_dir().'/ops004-'.bin2hex(random_bytes(8));
        $this->app->useStoragePath($this->temporaryStorage);
    }

    protected function tearDown(): void
    {
        (new \Illuminate\Filesystem\Filesystem())->deleteDirectory($this->temporaryStorage);
        parent::tearDown();
    }

    public function test_cleanup_keeps_only_the_three_newest_backups_and_ignores_other_files(): void
    {
        $disk = Storage::fake('ops004');
        config(['backup.backup.name' => 'clinic', 'backup.backup.destination.disks' => ['ops004']]);

        $names = [
            '2020-01-01-00-00-00.zip',
            '2021-01-01-00-00-00.zip',
            '2022-01-01-00-00-00.zip',
            '2022-01-01-00-00-01.zip',
            '2022-01-01-00-00-02.zip',
        ];
        // Deliberately write in reverse order: retention uses backup date, not insertion order.
        foreach (array_reverse($names) as $name) {
            $disk->makeDirectory('clinic');
            $zip = new ZipArchive();
            $zip->open($disk->path('clinic/'.$name), ZipArchive::CREATE);
            $zip->addFromString('database.sql', 'SELECT 1;');
            $zip->close();
        }
        $disk->put('clinic/notes.txt', 'Keep this file');
        $disk->put('another-app/unrelated.zip', 'Keep this file');

        $this->artisan('backup:clean', ['--disable-notifications' => true])->assertSuccessful();

        foreach ($names as $index => $name) {
            $this->assertSame($index >= 2, $disk->exists('clinic/'.$name), $name);
        }
        $disk->assertExists(['clinic/notes.txt', 'another-app/unrelated.zip']);
        // A second cleanup is harmless, even when all retained backups are years old.
        $this->artisan('backup:clean', ['--disable-notifications' => true])->assertSuccessful();
        $disk->assertExists(array_map(fn ($name) => 'clinic/'.$name, array_slice($names, 2)));
    }

    public function test_cleanup_preserves_zero_one_and_two_backups(): void
    {
        $disk = Storage::fake('ops004');
        config(['backup.backup.name' => 'clinic', 'backup.backup.destination.disks' => ['ops004']]);

        foreach (range(0, 2) as $count) {
            if ($count > 0) {
                $disk->put("clinic/2020-01-0{$count}-00-00-00.zip", 'backup');
            }
            $this->artisan('backup:clean', ['--disable-notifications' => true])->assertSuccessful();
            $this->assertCount($count, $disk->allFiles('clinic'));
        }
    }

    public function test_envoy_cleans_only_after_a_successful_backup(): void
    {
        $envoy = file_get_contents(base_path('Envoy.blade.php'));
        preg_match("/@task\('backupDatabase'.*?\n(.*?)@endtask/s", $envoy, $matches);
        $script = preg_replace('/^\{\{ logMessage.*\n/m', '', $matches[1]);
        $script = str_replace('{{ $newReleaseDir }}', escapeshellarg(base_path()), $script);

        foreach ([0, 1] as $backupExitCode) {
            // Run the actual task shell with a harmless PHP stub; no backup or deletion occurs.
            $stub = 'php8.1() { echo "$*"; if [ "$2" = "backup:run" ]; then return '.$backupExitCode.'; fi; return 0; };' . "\n";
            $process = new Process(['bash', '-c', $stub.$script]);
            $process->run();
            $this->assertSame($backupExitCode, $process->getExitCode());
            $this->assertSame($backupExitCode === 0, str_contains($process->getOutput(), 'backup:clean'));
        }
    }
}
