<?php

namespace App\Filament\Resources\DoctorResource\Pages;

use App\Filament\Resources\DoctorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Throwable;

class ListDoctors extends ListRecords
{
    protected static string $resource = DoctorResource::class;

    public function getFeedUrl(): string
    {
        return route('yml-feed.show');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_doctors_from_api')
                ->label('Импорт врачей из API')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->disabled(fn(): bool => auth()->user()->hasRole('demo'))
                ->requiresConfirmation()
                ->modalHeading('Импорт врачей из API')
                ->modalSubheading('Будут добавлены только новые врачи по uuid. Существующие записи не изменяются.')
                ->modalButton('Импортировать')
                ->action(function (): void {
                    try {
                        set_time_limit(180);
                        $service = app(\App\Services\DoctorImportFromBookingApiService::class);
                        $stats = $service->import();

                        $lines = [
                            "Создано: {$stats['created']}",
                            "Пропущено (уже есть): {$stats['skipped_existing']}",
                            "Пропущено (без external_id): {$stats['skipped_missing_external_id']}",
                            "Пропущено (external_id не UUID): {$stats['skipped_invalid_external_id']}",
                            "Пропущено (дубликат в API): {$stats['skipped_duplicate_in_api']}",
                            "Городов обработано: {$stats['cities_processed']} из {$stats['cities_total']}",
                            "Разрешённых клиник обработано: {$stats['clinics_allowed_processed']}",
                        ];

                        if (!empty($stats['errors'])) {
                            $lines[] = 'Ошибок: ' . count($stats['errors']);
                        }

                        Notification::make()
                            ->title('Импорт врачей завершён')
                            ->body(implode(PHP_EOL, $lines))
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Ошибка импорта врачей')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('view_feed')
                ->label('Просмотреть фид врачей')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url($this->getFeedUrl())
                ->openUrlInNewTab(),
            Actions\CreateAction::make(),
            Actions\Action::make('generate_yml_feed')
                ->label('Генерировать YML фид врачей')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    try {
                        $ymlFeedService = app(\App\Services\YmlFeedService::class);
                        set_time_limit(120);

                        $feeds = $ymlFeedService->generateDoctorsFeedsForActiveCities();
                        $savedFeeds = $ymlFeedService->saveFeedsToFiles($feeds);
                        $summary = collect($savedFeeds)
                            ->map(fn (array $feed) => "{$feed['city_name']} ({$feed['city_slug']}): {$feed['filename']}")
                            ->implode(PHP_EOL);

                        Notification::make()
                            ->title('Фиды успешно сгенерированы')
                            ->body($summary . PHP_EOL . 'Legacy ссылка default-города: ' . $this->getFeedUrl())
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Открыть default feed')
                                    ->url($this->getFeedUrl())
                                    ->openUrlInNewTab(),
                                \Filament\Notifications\Actions\Action::make('download')
                                    ->label('Скачать default XML')
                                    ->url(route('yml-feed.download', data_get(collect($savedFeeds)->firstWhere('is_default', true), 'filename', 'doctors_feed.xml')))
                                    ->openUrlInNewTab()
                            ])
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Ошибка генерации фида')
                            ->body('Произошла ошибка: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Генерация YML фидов врачей')
                ->modalSubheading('Будут созданы отдельные XML-файлы по всем активным городам. Legacy ссылка /yml-feed/doctors останется для default-города.')
                ->modalButton('Генерировать все фиды')
        ];
    }

}
