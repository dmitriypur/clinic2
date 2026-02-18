<?php

namespace App\Models\Traits;

use App\Services\CityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait HasCityScope
{
    public static function bootHasCityScope(): void
    {
        if (app()->runningInConsole() || request()->is('admin/*') || request()->is('livewire/*')) {
            return;
        }

        static::addGlobalScope('city', function (Builder $builder) {
            $table = $builder->getModel()->getTable();
            $pivotTable = self::getCityPivotTableName($table);

            // Если pivot-таблица еще не создана (например, миграция не применена),
            // не накладываем city-scope, чтобы избежать SQL ошибок.
            if (!Schema::hasTable($pivotTable)) {
                return;
            }

            $cityService = app(CityService::class);
            $currentCity = $cityService->getCurrentCity();

            if ($currentCity) {
                $builder->where(function (Builder $query) use ($currentCity) {
                    $table = $query->getModel()->getTable();
                    $pivotTable = self::getCityPivotTableName($table);
                    $foreignKey = str()->singular($table) . '_id';

                    // Используем whereExists вместо whereHas для оптимизации (избегаем N+1)
                    $query->whereExists(function ($subQuery) use ($table, $pivotTable, $foreignKey, $currentCity) {
                        $subQuery->selectRaw(1)
                            ->from($pivotTable)
                            ->whereColumn("{$pivotTable}.{$foreignKey}", "{$table}.id")
                            ->where("{$pivotTable}.city_id", $currentCity->id);
                    })->orWhereNotExists(function ($subQuery) use ($table, $pivotTable, $foreignKey) {
                        // Или показываем записи, которые не привязаны ни к одному городу
                        $subQuery->selectRaw(1)
                            ->from($pivotTable)
                            ->whereColumn("{$pivotTable}.{$foreignKey}", "{$table}.id");
                    });
                });
            } else {
                $builder->whereDoesntHave('cities');
            }
        });
    }

    /**
     * Получить имя pivot-таблицы для связи с городами
     *
     * @param string $table Имя таблицы модели
     * @return string Имя pivot-таблицы
     */
    protected static function getCityPivotTableName(string $table): string
    {
        // Для всех таблиц: city_doctor, city_page, city_service (единственное число)
        // Убираем 's' в конце, если есть
        $singular = str_ends_with($table, 's') ? substr($table, 0, -1) : $table;
        return 'city_' . $singular;
    }
}
