<?php

declare(strict_types=1);

namespace App\ViewData;

use App\Models\Doctor;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DoctorPageViewData
{
    public function __construct(
        private readonly Doctor $doctor
    ) {}

    public function toArray(): array
    {
        $education = collect(data_get($this->doctor->extra, 'education', []))
            ->map(function ($institution) {
                $items = collect(data_get($institution, 'educational_institution', []))
                    ->map(fn($item) => [
                        'year' => trim((string) data_get($item, 'year')),
                        'specialty' => trim((string) data_get($item, 'specialty')),
                        'level' => trim((string) data_get($item, 'level')),
                    ])
                    ->filter(fn(array $item) => filled($item['year']) || filled($item['specialty']) || filled($item['level']))
                    ->values();

                return [
                    'title' => trim((string) data_get($institution, 'title')),
                    'items' => $items,
                ];
            })
            ->filter(fn(array $institution) => filled($institution['title']) || $institution['items']->isNotEmpty())
            ->values();

        $professionalDevelopment = collect(data_get($this->doctor->extra, 'professional_development', []))
            ->map(fn($item) => [
                'year' => trim((string) data_get($item, 'year')),
                'title' => trim((string) data_get($item, 'title')),
            ])
            ->filter(fn(array $item) => filled($item['year']) || filled($item['title']))
            ->values();

        $skills = collect(data_get($this->doctor->extra, 'skills', []))
            ->map(fn($item) => trim((string) (is_array($item) ? data_get($item, 'title') : $item)))
            ->filter(fn($item) => filled($item))
            ->values();

        $documents = $this->doctor->getMedia('documents')->sortByDesc('order_column')->values();
        $hasDoctorBio = filled(trim((string) $this->doctor->bio));

        return [
            'ratingText' => $this->sanitizeText(data_get($this->doctor->extra, 'rating')) ?: '100% пациентов рекомендуют врача',
            'topFacts' => $this->buildTopFacts(),
            'reviewItems' => $this->buildReviewItems(),
            'education' => $education,
            'professionalDevelopment' => $professionalDevelopment,
            'skills' => $skills,
            'documents' => $documents,
            'hasDesktopInfoSections' => $education->isNotEmpty()
                || $professionalDevelopment->isNotEmpty()
                || $skills->isNotEmpty()
                || $documents->isNotEmpty(),
            'mobileSections' => $this->buildMobileSections(
                hasDoctorBio: $hasDoctorBio,
                education: $education,
                professionalDevelopment: $professionalDevelopment,
                skills: $skills,
                documents: $documents,
            ),
            'hasInfoSections' => $hasDoctorBio
                || $education->isNotEmpty()
                || $professionalDevelopment->isNotEmpty()
                || $skills->isNotEmpty()
                || $documents->isNotEmpty(),
        ];
    }

    private function buildTopFacts(): Collection
    {
        return collect([
            filled(data_get($this->doctor->extra, 'seniority'))
                ? 'Стаж работы: ' . trim((string) data_get($this->doctor->extra, 'seniority'))
                : null,
            filled(data_get($this->doctor->extra, 'receives'))
                ? trim((string) data_get($this->doctor->extra, 'receives'))
                : null,
        ])
            ->filter()
            ->values();
    }

    private function buildReviewItems(): Collection
    {
        return collect(data_get($this->doctor->extra, 'reviews', []))
            ->map(function ($review) {
                $url = trim((string) data_get($review, 'url'));
                $uuid = trim((string) data_get($review, 'uuid'));

                if ($url === '' || $uuid === '') {
                    return null;
                }

                $media = $this->doctor->getFirstMedia($uuid);

                if (!$media) {
                    return null;
                }

                return [
                    'url' => $url,
                    'label' => $this->resolveReviewLabel($url),
                    'media' => $media,
                ];
            })
            ->filter()
            ->sortBy(fn(array $item) => $item['label'] === 'Яндекс отзывы' ? 0 : 1)
            ->values();
    }

    private function buildMobileSections(
        bool $hasDoctorBio,
        Collection $education,
        Collection $professionalDevelopment,
        Collection $skills,
        Collection $documents,
    ): Collection {
        $sections = collect();

        if ($education->isNotEmpty()) {
            $sections->push([
                'key' => 'education',
                'title' => 'Образование',
            ]);
        } elseif ($hasDoctorBio) {
            $sections->push([
                'key' => 'about',
                'title' => 'Информация о специалисте',
            ]);
        }

        if ($professionalDevelopment->isNotEmpty()) {
            $sections->push([
                'key' => 'development',
                'title' => 'Повышение квалификации',
            ]);
        }

        if ($skills->isNotEmpty()) {
            $sections->push([
                'key' => 'skills',
                'title' => 'Профессиональные навыки',
            ]);
        }

        if ($documents->isNotEmpty()) {
            $sections->push([
                'key' => 'documents',
                'title' => 'Документы, подтверждающие квалификацию',
            ]);
        }

        return $sections;
    }

    private function sanitizeText(?string $value): string
    {
        $text = trim((string) $value);

        return preg_match('/[\p{L}\p{N}]/u', strip_tags($text)) ? $text : '';
    }

    private function resolveReviewLabel(string $url): string
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        if (Str::contains($host, 'prodoctorov')) {
            return 'Продокторов';
        }

        return 'Яндекс отзывы';
    }
}
