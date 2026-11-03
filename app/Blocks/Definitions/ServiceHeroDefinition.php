<?php

declare(strict_types=1);

namespace App\Blocks\Definitions;

use App\Blocks\AbstractBlockDefinition;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeFileUpload;
use App\Models\Block;
use App\Services\ServiceHeroImageService;
use Filament\Forms;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class ServiceHeroDefinition extends AbstractBlockDefinition
{
    public function type(): BlockType { return BlockType::SERVICE_HERO; }

    public function label(): string { return 'Hero-баннер услуги'; }

    public function view(): string { return 'components.block.service-hero'; }

    public function formSchema(): array
    {
        return [
            Forms\Components\Section::make('Содержимое hero-баннера')
                ->description('Заголовок задаётся в основном поле «Заголовок» блока. Загрузите готовый фон целиком: с изображением и нижней плашкой, но без заголовка, описания и кнопки.')
                ->schema([
                    Forms\Components\Textarea::make('payload.description')->label('Описание')->rows(3)->maxLength(300)->columnSpanFull(),
                    SafeFileUpload::make('payload.image')->safeImages()->disk('public')->directory('service-hero')->label('Готовый фон для компьютера')->helperText('1920 × 492 px; JPEG и PNG автоматически преобразуются в WebP.')->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(ServiceHeroImageService::class)->store($file))->required(),
                    SafeFileUpload::make('payload.mobile_image')->safeImages()->disk('public')->directory('service-hero')->label('Готовый фон для телефона')->helperText('390 × 600 px; JPEG и PNG автоматически преобразуются в WebP. Если не задан, используется фон для компьютера.')->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(ServiceHeroImageService::class)->store($file)),
                    Forms\Components\TextInput::make('payload.button_text')->label('Текст кнопки')->default('Записаться на приём')->maxLength(80)->required(),
                    Forms\Components\TextInput::make('payload.button_url')
                        ->label('Ссылка кнопки')
                        ->helperText('Укажите адрес страницы, якорь, телефон или email. Если оставить поле пустым, откроется виджет записи.')
                        ->placeholder('/services или https://example.ru')
                        ->maxLength(2048)
                        ->rules([fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                            if (trim((string) $value) !== '' && self::safeButtonUrl($value) === null) {
                                $fail('Укажите корректную ссылку: /путь, #якорь, https://, http://, tel: или mailto:.');
                            }
                        }]),
                ]),
        ];
    }

    public function wrapperClassName(Block $block): ?string { return 'bg-white'; }

    public function viewData(Block $block): array
    {
        $payload = (array) ($block->payload ?? []);
        $desktop = filled($payload['image'] ?? null) ? '/storage/'.ltrim((string) $payload['image'], '/') : null;
        $mobilePath = filled($payload['mobile_image'] ?? null) ? $payload['mobile_image'] : ($payload['image'] ?? null);
        $mobile = filled($mobilePath) ? '/storage/'.ltrim((string) $mobilePath, '/') : null;
        $images = app(ServiceHeroImageService::class);

        return [
            'heroId' => 'service-hero-'.($block->getKey() ?? 'preview'),
            'title' => trim((string) $block->title),
            'description' => trim((string) ($payload['description'] ?? '')),
            'buttonText' => trim((string) ($payload['button_text'] ?? '')) ?: 'Записаться на приём',
            'buttonUrl' => self::safeButtonUrl($payload['button_url'] ?? null),
            'desktopImage' => $desktop,
            'mobileImage' => $mobile,
            'desktopWebp' => $images->webpUrl($payload['image'] ?? null),
            'mobileWebp' => $images->webpUrl($mobilePath),
        ];
    }

    private static function safeButtonUrl(mixed $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '' || preg_match('/[\s\x00-\x1f\x7f\\\\]/u', $url)) {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }
        if (str_starts_with($url, '#') && strlen($url) > 1) {
            return $url;
        }
        if (preg_match('~^https?://~i', $url) && filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        if (preg_match('/^tel:\+?[0-9().-]+$/i', $url) || preg_match('/^mailto:[^@]+@[^@]+$/i', $url)) {
            return $url;
        }

        return null;
    }
}
