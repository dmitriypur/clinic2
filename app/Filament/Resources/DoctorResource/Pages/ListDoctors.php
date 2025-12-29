<?php

namespace App\Filament\Resources\DoctorResource\Pages;

use App\Filament\Resources\DoctorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;

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
                        // Генерируем фид напрямую, без HTTP запроса
                        $ymlFeedService = app(\App\Services\YmlFeedService::class);
                        set_time_limit(120); // Увеличиваем лимит времени
                        
                        $feedContent = $ymlFeedService->generateDoctorsFeed();
                        $filename = $ymlFeedService->saveFeedToFile($feedContent);
                        
                        Notification::make()
                            ->title('Фид успешно сгенерирован')
                            ->body('Файл сохранен: ' . $filename . PHP_EOL . 'Ссылка для просмотра: ' . $this->getFeedUrl())
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Просмотреть')
                                    ->url($this->getFeedUrl())
                                    ->openUrlInNewTab(),
                                \Filament\Notifications\Actions\Action::make('download')
                                    ->label('Скачать')
                                    ->url(route('yml-feed.download', $filename))
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
                ->modalHeading('Генерация YML фида врачей')
                ->modalSubheading('Будет создан XML файл с данными всех врачей для передачи в Яндекс')
                ->modalButton('Генерировать')
        ];
    }

}
