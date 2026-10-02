<?php

namespace App\Filament\Pages;

use App\Models\City;
use App\Models\SiteSearchQuery;
use DateTimeImmutable;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

class SearchAnalytics extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationGroup = 'Аналитика';

    protected static ?string $navigationLabel = 'Статистика поиска';

    protected static ?string $title = 'Статистика поиска';

    protected static ?string $slug = 'search-analytics';

    protected static string $view = 'filament.pages.search-analytics';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $cityId = null;

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->dateFrom = now()->subDays(90)->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function hydrate(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'cityId'], true)) {
            $this->resetPage();
        }
    }

    protected function getViewData(): array
    {
        $searches = $this->filteredSearches();

        $counts = (clone $searches)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) as without_results')
            ->first();

        return [
            'cities' => City::query()->orderBy('name')->pluck('name', 'id'),
            'totalSearches' => (int) $counts->total,
            'withoutResults' => (int) $counts->without_results,
            'topQueries' => (clone $searches)
                ->select('query')
                ->selectRaw('COUNT(*) as searches')
                ->selectRaw('SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) as without_results')
                ->groupBy('query')
                ->orderByDesc('searches')
                ->orderBy('query')
                ->limit(20)
                ->get(),
            'zeroResultQueries' => (clone $searches)
                ->where('results_count', 0)
                ->select('query')
                ->selectRaw('COUNT(*) as searches')
                ->groupBy('query')
                ->orderByDesc('searches')
                ->orderBy('query')
                ->limit(20)
                ->get(),
            'recentQueries' => (clone $searches)
                ->with('city:id,name')
                ->latest('created_at')
                ->latest('id')
                ->paginate(25),
        ];
    }

    private function filteredSearches(): Builder
    {
        $from = $this->validDate($this->dateFrom)
            ?? now()->subDays(90)->startOfDay()->toDateTimeImmutable();
        $to = $this->validDate($this->dateTo)
            ?? now()->startOfDay()->toDateTimeImmutable();

        return SiteSearchQuery::query()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to->modify('+1 day'))
            ->when(
                $this->cityId !== null && ctype_digit($this->cityId),
                fn (Builder $query) => $query->where('city_id', (int) $this->cityId),
            );
    }

    private function validDate(?string $date): ?DateTimeImmutable
    {
        if ($date === null) {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date
            ? $parsed
            : null;
    }
}
