<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Page;
use App\Services\CityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CityScopeQueryProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_city_scoped_queries_inspect_the_pivot_table_once_per_request(): void
    {
        $city = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
        ]);

        Page::query()->withoutGlobalScopes()->create([
            'title' => 'Страница',
            'handle' => 'profiled-page',
            'active' => true,
        ]);

        app(CityService::class)->setCurrentCity($city);

        $originalRequest = app('request');
        $runningInConsole = new \ReflectionProperty(app(), 'isRunningInConsole');
        $wasRunningInConsole = app()->runningInConsole();
        $runningInConsole->setValue(app(), false);
        Model::clearBootedModels();

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $this->assertSame(1, Page::query()->count());
            $this->assertSame(1, Page::query()->count());

            $schemaQueryCount = collect(DB::getQueryLog())
                ->filter(static function (array $query): bool {
                    return str_contains($query['query'], 'information_schema.tables')
                        || str_contains($query['query'], 'sqlite_master');
                })
                ->count();

            $this->assertSame(1, $schemaQueryCount);

            app()->instance('request', Request::create('/', 'GET'));
            DB::flushQueryLog();

            $this->assertSame(1, Page::query()->count());

            $freshRequestSchemaQueryCount = collect(DB::getQueryLog())
                ->filter(static function (array $query): bool {
                    return str_contains($query['query'], 'information_schema.tables')
                        || str_contains($query['query'], 'sqlite_master');
                })
                ->count();

            $this->assertSame(1, $freshRequestSchemaQueryCount);
        } finally {
            DB::disableQueryLog();
            app()->instance('request', $originalRequest);
            $runningInConsole->setValue(app(), $wasRunningInConsole);
            Model::clearBootedModels();
        }
    }
}
