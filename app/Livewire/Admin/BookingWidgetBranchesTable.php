<?php

namespace App\Livewire\Admin;

use App\Models\BookingWidgetBranchOrder;
use App\Services\BookingWidgetBranchSyncService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class BookingWidgetBranchesTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    public int $cityId;

    public ?string $syncError = null;

    public function mount(int $cityId): void
    {
        $this->cityId = $cityId;
        $this->mountInteractsWithTable();
    }

    public function syncBranchesIfNeeded(): void
    {
        try {
            app(BookingWidgetBranchSyncService::class)->syncCity($this->cityId);
        } catch (\Throwable $exception) {
            report($exception);
            $this->syncError = 'Не удалось обновить филиалы из booking API.';
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('clinic_name')
                    ->label('Клиника')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Филиал')
                    ->wrap()
                    ->searchable(),
                TextInputColumn::make('sort_order')
                    ->label('Порядок')
                    ->type('number')
                    ->step(1)
                    ->rules(['nullable', 'integer'])
                    ->extraInputAttributes(['class' => 'w-24']),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->orderByRaw('sort_order IS NULL, sort_order ASC')
                    ->orderBy('clinic_name')
                    ->orderBy('title');
            })
            ->emptyStateHeading('Для выбранного города нет филиалов');
    }

    private function getTableQuery(): Builder
    {
        return BookingWidgetBranchOrder::query()
            ->where('city_id', $this->cityId);
    }

    public function render()
    {
        return view('livewire.admin.booking-widget-branches-table');
    }
}
