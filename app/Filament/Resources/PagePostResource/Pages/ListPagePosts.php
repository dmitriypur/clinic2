<?php

namespace App\Filament\Resources\PagePostResource\Pages;

use App\Filament\Resources\ArticleImportResource;
use App\Filament\Resources\PagePostResource;
use App\Jobs\ImportArticle;
use App\Models\ArticleImport;
use App\Models\Category;
use App\Models\Doctor;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

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
                        ->required(fn(Get $get): bool => blank(trim((string) $get('source'))))
                        ->placeholder('https://docs.google.com/document/d/...'),
                    Forms\Components\Textarea::make('source')
                        ->label('Резервный импорт из текста')
                        ->required(fn(Get $get): bool => blank(trim((string) $get('document_url'))))
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
                    Forms\Components\Toggle::make('include_expert_opinion')
                        ->label('Добавить блок «Мнение эксперта»')
                        ->live()
                        ->default(false),
                    Forms\Components\Select::make('expert_id')
                        ->label('Врач для блока «Мнение эксперта»')
                        ->searchable()
                        ->options(
                            Doctor::query()
                                ->orderBy('surname')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn(Doctor $doctor) => [$doctor->id => trim($doctor->surname . ' ' . $doctor->name)])
                                ->all()
                        )
                        ->required(fn(Get $get): bool => (bool) $get('include_expert_opinion'))
                        ->visible(fn(Get $get): bool => (bool) $get('include_expert_opinion')),
                    Forms\Components\RichEditor::make('expert_body_html')
                        ->label('Текст мнения эксперта')
                        ->required(fn(Get $get): bool => (bool) $get('include_expert_opinion'))
                        ->visible(fn(Get $get): bool => (bool) $get('include_expert_opinion')),
                    Forms\Components\FileUpload::make('expert_image_path')
                        ->label('Фото врача для блока')
                        ->disk('local')
                        ->directory('article-imports/expert-images')
                        ->image()
                        ->required(fn(Get $get): bool => (bool) $get('include_expert_opinion'))
                        ->visible(fn(Get $get): bool => (bool) $get('include_expert_opinion'))
                        ->helperText('Используется только в блоке «Мнение эксперта» и не заменяет фото профиля врача.'),
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
                    $articleImport = ArticleImport::query()->create([
                        'staff_id' => auth()->id(),
                        'status' => ArticleImport::STATUS_QUEUED,
                        'document_url' => trim((string) ($data['document_url'] ?? '')) ?: null,
                        'payload' => $data,
                    ]);

                    ImportArticle::dispatch($articleImport->id);

                    Notification::make()
                        ->title('Импорт поставлен в очередь')
                        ->body('Статус можно смотреть в разделе "Импорты статей". Когда задача завершится, статья появится в списке записей.')
                        ->success()
                        ->send();

                    $this->redirect(ArticleImportResource::getUrl('index'));
                }),
        ];
    }
}
