<?php

namespace App\Filament\Resources\PagePostResource\Pages;

use App\Filament\Resources\PagePostResource;
use App\Models\Category;
use App\Models\Doctor;
use App\Services\ArticleImport\ArticleImportException;
use App\Services\ArticleImport\ArticleImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListPagePosts extends ListRecords
{
    protected static string $resource = PagePostResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('importArticle')
                ->label('Импорт статьи')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalWidth('4xl')
                ->disabled(auth()->user()->hasRole('demo'))
                ->form([
                    Forms\Components\TextInput::make('document_url')
                        ->label('Ссылка на Google Docs')
                        ->url()
                        ->placeholder('https://docs.google.com/document/d/...'),
                    Forms\Components\Textarea::make('source')
                        ->label('Резервный импорт из текста')
                        ->rows(18)
                        ->helperText("Если ссылка недоступна, можно вставить структурированный текст: # Заголовок, затем блоки через ##, FAQ через ## FAQ и вопросы через ###.")
                        ->placeholder("# Заголовок статьи\nТема: Близорукость\nТеги: близорукость, лечение\n\n## Первый блок\nТекст блока\n\n## FAQ\n### Вопрос?\nОтвет"),
                    Forms\Components\Select::make('category_id')
                        ->label('Категория')
                        ->options(Category::query()->orderBy('title')->pluck('title', 'id'))
                        ->default(fn() => Category::query()->where('handle', 'stati')->value('id'))
                        ->required(),
                    Forms\Components\Select::make('author_id')
                        ->label('Автор статьи')
                        ->searchable()
                        ->options(
                            Doctor::query()
                                ->orderBy('surname')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn(Doctor $doctor) => [$doctor->id => trim($doctor->surname . ' ' . $doctor->name)])
                                ->all()
                        ),
                    Forms\Components\TextInput::make('theme')
                        ->label('Тема статьи')
                        ->helperText('Если в документе есть строка "Тема:", она подставится автоматически.'),
                    Forms\Components\TextInput::make('breadcrumbs_title')
                        ->label('Заголовок для хлебных крошек'),
                    Forms\Components\Toggle::make('append_default_blocks')
                        ->label('Добавить стандартные блоки внизу статьи')
                        ->default(true),
                    Forms\Components\Toggle::make('active')
                        ->label('Сразу опубликовать')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    try {
                        $result = app(ArticleImportService::class)->import($data);
                    } catch (ArticleImportException $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Импорт не выполнен')
                            ->body($exception->userMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Импорт не выполнен')
                            ->body("Этап: импорт статьи\nПричина: {$exception->getMessage()}\nПодробности записаны в лог.")
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $notification = Notification::make()
                        ->title('Статья импортирована')
                        ->body($result->hasWarnings()
                            ? "Создан черновик статьи, но есть предупреждения:\n" . implode("\n", $result->warnings)
                            : 'Создан черновик статьи. Сейчас откроется страница редактирования.');

                    ($result->hasWarnings() ? $notification->warning() : $notification->success())
                        ->send();

                    $this->redirect(PagePostResource::getUrl('edit', ['record' => $result->page]));
                }),
        ];
    }
}
