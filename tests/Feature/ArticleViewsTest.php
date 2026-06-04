<?php

namespace Tests\Feature;

use App\Enums\PageType;
use App\Filament\Pages\ArticleViewsImportPage;
use App\Models\Category;
use App\Models\City;
use App\Models\Page;
use App\Models\Staff;
use App\Services\ArticleViews\ArticleViewImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArticleViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_counter_and_links_existing_article(): void
    {
        $page = $this->createArticle('miopiia');
        $csvPath = $this->writeCsv([
            ['https://zrenie.clinic/stati/miopiia?utm=test#top', '1 173'],
        ]);

        $result = app(ArticleViewImportService::class)->import($csvPath);

        $this->assertSame(1, $result->created);
        $this->assertSame(1, $result->linked);
        $this->assertDatabaseHas('article_view_counters', [
            'page_path' => '/stati/miopiia',
            'handle' => 'miopiia',
            'page_id' => $page->id,
            'views_count' => 1173,
            'source' => 'yandex_csv',
        ]);
    }

    public function test_import_keeps_counter_for_missing_local_article(): void
    {
        $csvPath = $this->writeCsv([
            ['https://zrenie.clinic/stati/prod-only-article', '42'],
        ]);

        $result = app(ArticleViewImportService::class)->import($csvPath);

        $this->assertSame(1, $result->created);
        $this->assertSame(1, $result->missingLocalPage);
        $this->assertDatabaseHas('article_view_counters', [
            'page_path' => '/stati/prod-only-article',
            'handle' => 'prod-only-article',
            'page_id' => null,
            'views_count' => 42,
        ]);
    }

    public function test_import_updates_existing_counter(): void
    {
        $page = $this->createArticle('ambliopiya-u-detei');

        DB::table('article_view_counters')->insert([
            'page_path' => '/stati/ambliopiya-u-detei',
            'page_path_hash' => hash('sha256', '/stati/ambliopiya-u-detei'),
            'handle' => 'ambliopiya-u-detei',
            'page_id' => $page->id,
            'views_count' => 10,
            'source' => 'local',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $csvPath = $this->writeCsv([
            ['https://zrenie.clinic/stati/ambliopiya-u-detei/', '1 305'],
        ]);

        $result = app(ArticleViewImportService::class)->import($csvPath);

        $this->assertSame(1, $result->updated);
        $this->assertDatabaseHas('article_view_counters', [
            'page_path' => '/stati/ambliopiya-u-detei',
            'views_count' => 1305,
            'source' => 'yandex_csv',
        ]);
    }

    public function test_article_show_increments_local_view_counter(): void
    {
        $category = Category::query()->create([
            'title' => 'Статьи',
            'handle' => 'stati',
        ]);
        $page = $this->createArticle('test-article', ['category_id' => $category->id]);

        $this->get('/stati/test-article')->assertOk();
        $this->get('/stati/test-article')->assertOk();

        $this->assertSame(2, article_views_count($page->fresh()));
    }

    public function test_article_views_import_page_is_available_only_for_super_admin(): void
    {
        $superAdmin = $this->createStaff('super@example.test');
        Role::query()->create(['name' => 'super_admin', 'guard_name' => 'staff']);
        $superAdmin->assignRole('super_admin');

        $regularStaff = $this->createStaff('staff@example.test');

        $this->actingAs($superAdmin, 'staff')
            ->get('/admin/article-views-import')
            ->assertOk()
            ->assertSee('Импорт просмотров статей');

        $this->actingAs($regularStaff, 'staff')
            ->get('/admin/article-views-import')
            ->assertRedirect('/login');
    }

    public function test_filament_page_imports_uploaded_csv(): void
    {
        $staff = $this->createStaff('super-livewire@example.test');
        Role::query()->create(['name' => 'super_admin', 'guard_name' => 'staff']);
        $staff->assignRole('super_admin');
        $this->actingAs($staff, 'staff');

        $this->createArticle('krasnota-pod-glazami-u-rebenka');
        $csvPath = $this->writeCsv([
            ['https://zrenie.clinic/stati/krasnota-pod-glazami-u-rebenka', '5 972'],
        ], storage_path('app/article-view-imports/test.csv'));

        Livewire::test(ArticleViewsImportPage::class)
            ->set('data.csv_path', ['article-view-imports/test.csv'])
            ->call('import')
            ->assertSee('Обновлено');

        $this->assertDatabaseHas('article_view_counters', [
            'handle' => 'krasnota-pod-glazami-u-rebenka',
            'views_count' => 5972,
        ]);

        File::delete($csvPath);
    }

    private function createArticle(string $handle, array $overrides = []): Page
    {
        return Page::query()->create(array_merge([
            'title' => 'Article ' . $handle,
            'handle' => $handle,
            'type' => PageType::Posts,
            'active' => true,
        ], $overrides));
    }

    private function createStaff(string $email): Staff
    {
        return Staff::query()->create([
            'name' => 'Admin',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function writeCsv(array $rows, ?string $path = null): string
    {
        $path ??= storage_path('framework/testing/article-views-' . uniqid('', true) . '.csv');
        File::ensureDirectoryExists(dirname($path));

        $handle = fopen($path, 'wb');
        fputcsv($handle, ['Адрес, ур. 1', 'Адрес, ур. 2', 'Адрес страницы', 'Просмотры', 'Посетители'], ';');
        fputcsv($handle, ['Итого и средние', '', '', '37804', '20638'], ';');

        foreach ($rows as [$url, $views]) {
            fputcsv($handle, ['https://zrenie.clinic/', 'https://zrenie.clinic/stati/', $url, $views, '1'], ';');
        }

        fclose($handle);

        return $path;
    }
}
