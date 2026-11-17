<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnvoyBackupDownloadTest extends TestCase
{
    use RefreshDatabase;

    private const DOWNLOAD_URL = '/admin/backups/latest';

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->directory = sys_get_temp_dir().'/envoy-backups-'.bin2hex(random_bytes(8));
        File::makeDirectory($this->directory, 0700);
        config(['backup.envoy_directory' => $this->directory]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_super_admin_can_download_the_newest_completed_envoy_backup_from_the_menu(): void
    {
        $newest = '20261117-130000-abcdef123456-XyZ789.sql.gz';
        $older = '20261117-120000-abcdef123456.sql.gz';
        $archive = gzencode('SELECT 1;');

        File::put($this->directory.'/'.$newest, $archive);
        File::put($this->directory.'/'.$older, gzencode('SELECT 2;'));
        touch($this->directory.'/'.$older, time() + 60);
        File::put($this->directory.'/20261117-140000-abcdef123456-XyZ789.sql.gz.tmp', 'partial');
        File::put($this->directory.'/unrelated.sql.gz', 'unrelated');
        File::makeDirectory($this->directory.'/20261117-150000-abcdef123456-XyZ789.sql.gz');
        symlink($this->directory.'/unrelated.sql.gz', $this->directory.'/20261117-160000-abcdef123456-XyZ789.sql.gz');

        $this->actingAs($this->staffWithRole('super_admin'), 'staff')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Скачать последний бэкап БД')
            ->assertSee(self::DOWNLOAD_URL, false);

        $response = $this->get(self::DOWNLOAD_URL)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/gzip')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertDownload($newest);

        $this->assertSame($archive, $response->streamedContent());
    }

    public function test_anonymous_visitors_are_redirected_to_the_panel_login(): void
    {
        $this->get(self::DOWNLOAD_URL)->assertRedirect('/admin/login');
    }

    public function test_other_staff_cannot_see_the_link_or_download_a_backup(): void
    {
        File::put($this->directory.'/20261117-130000-abcdef123456-XyZ789.sql.gz', gzencode('SELECT 1;'));

        $this->actingAs($this->staffWithRole('panel_user'), 'staff')
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Скачать последний бэкап БД');

        $this->get(self::DOWNLOAD_URL)->assertForbidden();
    }

    public function test_super_admin_gets_a_notification_when_there_is_no_completed_backup(): void
    {
        File::put($this->directory.'/20261117-130000-abcdef123456-XyZ789.sql.gz.tmp', 'partial');

        $this->actingAs($this->staffWithRole('super_admin'), 'staff')
            ->get(self::DOWNLOAD_URL)
            ->assertRedirect('/admin')
            ->assertSessionHas('filament.notifications.0.title', 'Резервная копия пока недоступна');
    }

    private function staffWithRole(string $role): Staff
    {
        $staff = Staff::query()->create([
            'name' => 'Администратор',
            'email' => 'backup-admin@example.test',
            'password' => 'password',
        ]);
        $staff->assignRole(Role::findOrCreate($role, 'staff'));

        return $staff;
    }
}
