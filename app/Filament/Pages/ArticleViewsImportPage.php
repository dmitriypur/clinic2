<?php

namespace App\Filament\Pages;

use App\Services\ArticleViews\ArticleViewImportService;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ArticleViewsImportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Блог';

    protected static ?string $navigationLabel = 'Импорт просмотров статей';

    protected static ?string $title = 'Импорт просмотров статей';

    protected static ?string $slug = 'article-views-import';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.article-views-import';

    public ?array $data = [];

    public ?array $importResult = null;

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

        $this->form->fill([
            'csv_path' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('csv_path')
                    ->label('CSV-файл из Яндекс.Метрики')
                    ->disk('local')
                    ->directory('article-view-imports')
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
                    ])
                    ->required()
                    ->helperText('Нужны колонки “Адрес страницы” и “Просмотры”.'),
            ])
            ->statePath('data');
    }

    public function import(ArticleViewImportService $importService): void
    {
        $data = $this->form->getState();
        $csvPathState = $data['csv_path'] ?? '';
        $relativePath = is_array($csvPathState)
            ? (string) (array_values($csvPathState)[0] ?? '')
            : (string) $csvPathState;
        $absolutePath = Storage::disk('local')->path($relativePath);

        $result = $importService->import($absolutePath);
        $this->importResult = $result->toArray();

        Notification::make()
            ->title('Импорт просмотров статей завершен')
            ->success()
            ->send();
    }

    public function resultText(): ?string
    {
        if ($this->importResult === null) {
            return null;
        }

        return sprintf(
            'Создано: %d. Обновлено: %d. Привязано: %d. Не найдено локально: %d. Пропущено: %d.',
            $this->importResult['created'] ?? 0,
            $this->importResult['updated'] ?? 0,
            $this->importResult['linked'] ?? 0,
            $this->importResult['missingLocalPage'] ?? 0,
            $this->importResult['skipped'] ?? 0,
        );
    }
}
