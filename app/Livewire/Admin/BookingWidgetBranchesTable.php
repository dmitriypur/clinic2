<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\AuthorizesBookingWidgetSettings;
use App\Models\BookingWidgetBranchOrder;
use App\Services\BookingWidgetBranchSyncService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Component;

class BookingWidgetBranchesTable extends Component implements HasForms, HasTable
{
    use AuthorizesBookingWidgetSettings;
    use InteractsWithForms;
    use Tables\Concerns\InteractsWithTable {
        updateTableColumnState as private updateFilamentTableColumnState;
    }

    #[Locked]
    public int $cityId;

    public ?string $syncError = null;

    public function mount(int $cityId): void
    {
        $this->cityId = $cityId;
        $this->authorizeBookingWidgetSettingsAccess();
        $this->mountInteractsWithTable();
    }

    public function syncBranchesIfNeeded(): void
    {
        $this->authorizeBookingWidgetSettingsAccess(mutation: true);

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
                    ->disabled(fn (): bool => ! $this->canMutateBookingWidgetSettings())
                    ->extraInputAttributes(['class' => 'w-24'])
                    ->updateStateUsing(function (BookingWidgetBranchOrder $record, $state): mixed {
                        return $this->updateSortOrder($record, $state);
                    }),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->orderByRaw('sort_order IS NULL, sort_order ASC')
                    ->orderBy('clinic_name')
                    ->orderBy('title');
            })
            ->emptyStateHeading('Для выбранного города нет филиалов');
    }

    public function updateTableColumnState(string $column, string $record, mixed $input): mixed
    {
        $this->authorizeBookingWidgetSettingsAccess(mutation: true);

        return $this->updateFilamentTableColumnState($column, $record, $input);
    }

    private function getTableQuery(): Builder
    {
        return BookingWidgetBranchOrder::query()
            ->where('city_id', $this->cityId);
    }

    private function updateSortOrder(BookingWidgetBranchOrder $record, mixed $state): ?int
    {
        $this->authorizeBookingWidgetSettingsAccess(mutation: true);

        abort_unless($record->city_id === $this->cityId, 404);

        $value = is_numeric($state) ? (int) $state : null;

        $record->update([
            'sort_order' => $value,
        ]);

        return $value;
    }

    public function render()
    {
        return view('livewire.admin.booking-widget-branches-table', [
            'canSyncBranches' => $this->canMutateBookingWidgetSettings(),
        ]);
    }
}
