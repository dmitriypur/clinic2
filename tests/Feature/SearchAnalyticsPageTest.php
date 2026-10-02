<?php

namespace Tests\Feature;

use App\Filament\Pages\SearchAnalytics;
use App\Models\City;
use App\Models\SiteSearchQuery;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SearchAnalyticsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_search_analytics_page(): void
    {
        Role::findOrCreate('super_admin', 'staff');

        $staff = Staff::query()->create([
            'name' => 'Администратор',
            'email' => 'search-admin@example.test',
            'password' => 'password',
        ]);
        $staff->assignRole('super_admin');

        $this->assertTrue(Route::has('filament.admin.pages.search-analytics'));

        $this->actingAs($staff, 'staff')
            ->get('/admin/search-analytics')
            ->assertOk()
            ->assertSee('Статистика поиска');
    }

    public function test_page_summarizes_searches_from_the_last_ninety_days(): void
    {
        Role::findOrCreate('super_admin', 'staff');
        $staff = Staff::query()->create([
            'name' => 'Администратор',
            'email' => 'search-stats@example.test',
            'password' => 'password',
        ]);
        $staff->assignRole('super_admin');
        $this->actingAs($staff, 'staff');

        $city = City::query()->create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => true,
            'active' => true,
        ]);

        foreach ([2, 0, 1] as $resultsCount) {
            SiteSearchQuery::forceCreate([
                'query' => 'лазерная коррекция',
                'city_id' => $city->id,
                'results_count' => $resultsCount,
                'created_at' => now()->subDay(),
            ]);
        }

        SiteSearchQuery::forceCreate([
            'query' => 'катаракта',
            'city_id' => $city->id,
            'results_count' => 0,
            'created_at' => now()->subDays(3),
        ]);
        SiteSearchQuery::forceCreate([
            'query' => 'старый запрос',
            'city_id' => $city->id,
            'results_count' => 0,
            'created_at' => now()->subDays(100),
        ]);

        Livewire::test(SearchAnalytics::class)
            ->assertSeeInOrder(['Всего запросов', '4', 'Без результатов', '2'])
            ->assertSeeInOrder(['Запросы без результатов', 'катаракта', '1', 'лазерная коррекция', '1'])
            ->assertSee('лазерная коррекция')
            ->assertSee('катаракта')
            ->assertDontSee('старый запрос');
    }

    public function test_city_and_date_filters_apply_to_all_statistics(): void
    {
        Role::findOrCreate('super_admin', 'staff');
        $staff = Staff::query()->create([
            'name' => 'Администратор',
            'email' => 'search-filters@example.test',
            'password' => 'password',
        ]);
        $staff->assignRole('super_admin');
        $this->actingAs($staff, 'staff');

        $kirov = City::query()->create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => true,
            'active' => true,
        ]);
        $moscow = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => false,
            'active' => true,
        ]);

        foreach ([
            ['лазерная коррекция', $kirov->id, now()->subDay(), 2],
            ['катаракта', $moscow->id, now()->subDay(), 0],
            ['давний запрос', $kirov->id, now()->subDays(20), 0],
        ] as [$query, $cityId, $date, $resultsCount]) {
            SiteSearchQuery::forceCreate([
                'query' => $query,
                'city_id' => $cityId,
                'results_count' => $resultsCount,
                'created_at' => $date,
            ]);
        }

        $page = Livewire::test(SearchAnalytics::class)
            ->set('cityId', (string) $kirov->id)
            ->set('dateFrom', now()->subDays(7)->toDateString());

        $this->assertSame(1, $page->viewData('totalSearches'));
        $this->assertSame(0, $page->viewData('withoutResults'));
        $this->assertStringContainsString('лазерная коррекция', $page->html());
        $this->assertStringNotContainsString('катаракта', $page->html());
        $this->assertStringNotContainsString('давний запрос', $page->html());
    }

    public function test_regular_staff_cannot_open_search_analytics_or_see_its_navigation(): void
    {
        $staff = Staff::query()->create([
            'name' => 'Сотрудник',
            'email' => 'search-staff@example.test',
            'password' => 'password',
        ]);
        $this->actingAs($staff, 'staff');

        $this->assertFalse(SearchAnalytics::shouldRegisterNavigation());
        Livewire::test(SearchAnalytics::class)->assertForbidden();
    }

    public function test_revoked_super_admin_cannot_continue_using_an_open_page(): void
    {
        Role::findOrCreate('super_admin', 'staff');
        $staff = Staff::query()->create([
            'name' => 'Администратор',
            'email' => 'search-revoked@example.test',
            'password' => 'password',
        ]);
        $staff->assignRole('super_admin');
        $this->actingAs($staff, 'staff');

        $page = Livewire::test(SearchAnalytics::class);

        $staff->removeRole('super_admin');

        $page->set('dateFrom', now()->subDay()->toDateString())
            ->assertForbidden();
    }

    public function test_recent_searches_can_be_paginated(): void
    {
        Role::findOrCreate('super_admin', 'staff');
        $staff = Staff::query()->create([
            'name' => 'Администратор',
            'email' => 'search-pages@example.test',
            'password' => 'password',
        ]);
        $staff->assignRole('super_admin');
        $this->actingAs($staff, 'staff');

        $city = City::query()->create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => true,
            'active' => true,
        ]);

        for ($number = 1; $number <= 26; $number++) {
            SiteSearchQuery::forceCreate([
                'query' => sprintf('запрос %02d', $number),
                'city_id' => $number === 1 ? $city->id : null,
                'results_count' => 1,
                'created_at' => now()->subMinute(),
            ]);
        }

        $page = Livewire::test(SearchAnalytics::class);

        $this->assertSame(25, $page->viewData('recentQueries')->count());
        $page->call('setPage', 2);
        $this->assertSame(['запрос 01'], $page->viewData('recentQueries')->pluck('query')->all());

        $page->set('cityId', (string) $city->id);
        $this->assertSame(1, $page->viewData('recentQueries')->currentPage());
        $this->assertSame(['запрос 01'], $page->viewData('recentQueries')->pluck('query')->all());
    }
}
