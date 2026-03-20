<?php

namespace App\Livewire\Admin;

use App\Models\Doctor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BookingWidgetDoctorsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    public int $cityId;

    public function mount(int $cityId): void
    {
        $this->cityId = $cityId;
        $this->mountInteractsWithTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->paginated(false)
            ->defaultSort('surname')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Врач')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $builder) use ($search): void {
                            $builder
                                ->where('doctors.surname', 'like', "%{$search}%")
                                ->orWhere('doctors.name', 'like', "%{$search}%")
                                ->orWhere('doctors.speciality', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('speciality')
                    ->label('Специальность')
                    ->wrap(),
                TextInputColumn::make('doctor_widget_sort_order')
                    ->label('Выбрать врача')
                    ->type('number')
                    ->step(1)
                    ->rules(['nullable', 'integer'])
                    ->extraInputAttributes(['class' => 'w-24'])
                    ->updateStateUsing(function (Doctor $record, $state): mixed {
                        return $this->updateSortOrder($record->id, 'sort_order', $state);
                    }),
                TextInputColumn::make('clinic_widget_sort_order')
                    ->label('Выбрать клинику')
                    ->type('number')
                    ->step(1)
                    ->rules(['nullable', 'integer'])
                    ->extraInputAttributes(['class' => 'w-24'])
                    ->updateStateUsing(function (Doctor $record, $state): mixed {
                        return $this->updateSortOrder($record->id, 'clinic_sort_order', $state);
                    }),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->orderByRaw('city_doctor.sort_order IS NULL, city_doctor.sort_order ASC')
                    ->orderBy('doctors.surname')
                    ->orderBy('doctors.name');
            })
            ->emptyStateHeading('Для выбранного города нет врачей');
    }

    private function getTableQuery(): Builder
    {
        return Doctor::query()
            ->withoutGlobalScopes()
            ->select([
                'doctors.id',
                'doctors.name',
                'doctors.surname',
                'doctors.speciality',
                DB::raw('city_doctor.sort_order as doctor_widget_sort_order'),
                DB::raw('city_doctor.clinic_sort_order as clinic_widget_sort_order'),
            ])
            ->join('city_doctor', 'city_doctor.doctor_id', '=', 'doctors.id')
            ->where('city_doctor.city_id', $this->cityId);
    }

    private function updateSortOrder(int $doctorId, string $column, mixed $state): ?int
    {
        $value = is_numeric($state) ? (int) $state : null;

        DB::table('city_doctor')
            ->where('city_id', $this->cityId)
            ->where('doctor_id', $doctorId)
            ->update([
                $column => $value,
            ]);

        return $value;
    }

    public function render()
    {
        return view('livewire.admin.booking-widget-doctors-table');
    }
}
