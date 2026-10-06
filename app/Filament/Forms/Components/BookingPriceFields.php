<?php

namespace App\Filament\Forms\Components;

use App\Support\BookingPriceSchedule;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;

class BookingPriceFields
{
    public static function make(string $field, string $label): Group
    {
        return Group::make()
            ->statePath("_booking_price_editor.{$field}")
            // Build actions for each repeater item so they bind to its container.
            ->schema(fn (): array => [
                Hidden::make('original_amount'),
                Hidden::make('current_ends_on'),
                Hidden::make('snapshot')->default(fn () => BookingPriceSchedule::fingerprint([], $field)),
                Hidden::make('original_scheduled')->default([]),
                Hidden::make('action'),
                TextInput::make('amount')
                    ->label($label)
                    ->suffix('₽')
                    ->inputMode('decimal')
                    // Native live binding updates local state immediately and only
                    // delays the request, so a quick action cannot lose typed text.
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                        if (filled($state) && $get('action') === 'clear') {
                            $set('action', null);
                        }
                    })
                    ->helperText('Пусто — собственная цена не задана. Изменение применяется после сохранения карточки.'),
                Placeholder::make('current_price')
                    ->label('')
                    ->content(fn (Get $get): string => filled($get('original_amount'))
                        ? 'Сейчас действует '.$get('original_amount').' ₽'.(filled($get('current_ends_on')) ? ' по '.date('d.m.Y', strtotime($get('current_ends_on'))).' включительно' : '')
                        : 'Сейчас собственная цена не задана.'),
                Grid::make(2)->schema([
                    DatePicker::make('starts_on')
                        ->label('Действует с')
                        ->default(fn () => now()->toDateString())
                        ->minDate(fn () => now()->toDateString())
                        ->required(fn (Get $get): bool => self::editing($get))
                        ->live()
                        ->dehydratedWhenHidden(),
                    DatePicker::make('ends_on')
                        ->label('Действует до')
                        ->minDate(fn (Get $get) => $get('starts_on') ?: now()->toDateString())
                        ->helperText('Включительно. Пусто — до следующего изменения.')
                        ->dehydratedWhenHidden(),
                ])->visible(fn (Get $get): bool => self::editing($get)),
                Placeholder::make('period_effect')
                    ->label('')
                    ->visible(fn (Get $get): bool => self::editing($get))
                    ->content('До начала действует прежняя цена. После окончания старая цена не возвращается.'),
                Repeater::make('scheduled')
                    ->label('Расписание цен')
                    // Keep row keys until the schedule validator attaches errors.
                    ->mutateDehydratedStateUsing(fn (?array $state): array => $state ?? [])
                    ->helperText('Следующая цена заменяет предыдущую с даты начала. Изменения применяются после сохранения карточки.')
                    ->defaultItems(0)
                    ->addActionLabel('Добавить цену')
                    ->reorderable(false)
                    ->collapsible()
                    ->collapsed(fn (ComponentContainer $item): bool => filled(data_get($item->getRawState(), 'price')))
                    ->deleteAction(fn (Action $action) => $action->label('Отменить цену')->tooltip('Отменить эту цену'))
                    ->itemLabel(function (array $state): string {
                        $amount = $state['price'] ?? null;
                        $start = $state['starts_on'] ?? null;
                        $end = $state['ends_on'] ?? null;

                        return filled($amount) && filled($start)
                            ? $amount.' ₽ с '.date('d.m.Y', strtotime($start)).(filled($end) ? ' по '.date('d.m.Y', strtotime($end)) : ' · до следующей смены')
                            : 'Новая цена';
                    })
                    ->schema([
                        TextInput::make('price')->label('Цена')->suffix('₽')->inputMode('decimal')->required()->live(),
                        Grid::make(2)->schema([
                            DatePicker::make('starts_on')->label('Действует с')->required()
                                ->default(fn () => now()->addDay()->toDateString())
                                ->minDate(fn () => now()->toDateString())->live(),
                            DatePicker::make('ends_on')->label('Действует до')
                                ->minDate(fn (Get $get) => $get('starts_on') ?: now()->toDateString())
                                ->helperText('Включительно. Пусто — до следующей смены.'),
                        ]),
                    ])
                    ->visible(fn (Get $get): bool => ! self::clearing($get))
                    ->dehydratedWhenHidden(),
                Placeholder::make('clear_effect')->label('')
                    ->visible(fn (Get $get): bool => self::clearing($get))
                    ->content('При сохранении собственная цена и все её запланированные изменения будут убраны.'),
                Actions::make([
                    Action::make('edit_period')
                        ->label('Изменить срок')
                        ->color('gray')
                        ->visible(fn (Get $get): bool => filled($get('original_amount')) && $get('action') === null && ! self::editing($get))
                        ->action(function (Get $get, Set $set): void {
                            $set('action', 'edit_period');
                            $set('starts_on', now()->toDateString());
                            $set('ends_on', $get('current_ends_on'));
                        }),
                    Action::make('clear_price')
                        ->label('Убрать цену')
                        ->color('gray')
                        ->visible(fn (Get $get): bool => $get('action') !== 'clear')
                        ->action(function (Set $set): void {
                            $set('action', 'clear');
                            $set('amount', null);
                        }),
                    Action::make('reset_price_input')
                        ->label('Отменить изменения')
                        ->color('gray')
                        ->visible(fn (Get $get): bool => $get('action') !== null || BookingPriceSchedule::normalize($get('amount')) !== BookingPriceSchedule::normalize($get('original_amount')) || array_values($get('scheduled') ?? []) !== array_values($get('original_scheduled') ?? []))
                        ->action(function (Get $get, Set $set): void {
                            $set('action', null);
                            $set('amount', $get('original_amount'));
                            $set('starts_on', now()->toDateString());
                            $set('ends_on', null);
                            $set('scheduled', $get('original_scheduled') ?? []);
                        }),
                ])->alignStart(),
            ]);
    }

    private static function editing(Get $get): bool
    {
        return filled($get('amount'))
            && $get('action') !== 'clear'
            && ($get('action') === 'edit_period'
                || BookingPriceSchedule::normalize($get('amount')) !== BookingPriceSchedule::normalize($get('original_amount')));
    }

    private static function clearing(Get $get): bool
    {
        return $get('action') === 'clear' || (filled($get('original_amount')) && blank($get('amount')));
    }
}
