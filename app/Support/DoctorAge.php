<?php

declare(strict_types=1);

namespace App\Support;

class DoctorAge
{
    public static function minMonths(?array $extra): ?int
    {
        return self::normalizeMonths(data_get($extra, 'age_min_months'));
    }

    public static function maxMonths(?array $extra): ?int
    {
        return self::normalizeMonths(data_get($extra, 'age_max_months'));
    }

    public static function receivesText(?array $extra): ?string
    {
        $text = trim((string) data_get($extra, 'receives_text'));

        return $text !== '' ? $text : null;
    }

    public static function display(?array $extra): ?string
    {
        return self::buildDisplay(
            self::minMonths($extra),
            self::maxMonths($extra),
            self::receivesText($extra),
        );
    }

    public static function buildDisplay(?int $minMonths, ?int $maxMonths, ?string $template = null): ?string
    {
        $templateDisplay = self::renderTemplate($template, $minMonths, $maxMonths);

        if ($templateDisplay !== null) {
            return $templateDisplay;
        }

        return self::buildDefaultDisplay($minMonths, $maxMonths);
    }

    public static function renderTemplate(?string $template, ?int $minMonths, ?int $maxMonths): ?string
    {
        $template = trim((string) $template);

        if ($template === '') {
            return null;
        }

        $requiresMin = str_contains($template, '{min}');
        $requiresMax = str_contains($template, '{max}');

        if (($requiresMin && $minMonths === null) || ($requiresMax && $maxMonths === null)) {
            return null;
        }

        return trim(strtr($template, [
            '{min}' => self::formatAgeMonths($minMonths) ?? '',
            '{max}' => self::formatAgeMonths($maxMonths) ?? '',
        ]));
    }

    public static function buildDefaultDisplay(?int $minMonths, ?int $maxMonths): ?string
    {
        if ($minMonths === null && $maxMonths === null) {
            return null;
        }

        if ($minMonths === 0 && $maxMonths !== null) {
            return sprintf('Ведет прием с 0 до %s', self::formatAgeMonths($maxMonths));
        }

        if ($minMonths !== null && $maxMonths !== null) {
            return sprintf('Ведет прием с %s до %s', self::formatAgeMonths($minMonths), self::formatAgeMonths($maxMonths));
        }

        if ($minMonths === 0) {
            return 'Ведет прием с 0';
        }

        if ($minMonths !== null) {
            return sprintf('Ведет прием с %s', self::formatAgeMonths($minMonths));
        }

        return sprintf('Ведет прием до %s', self::formatAgeMonths($maxMonths));
    }

    public static function formatAgeMonths(?int $months): ?string
    {
        if ($months === null || $months < 0) {
            return null;
        }

        if ($months === 0) {
            return 'рождения';
        }

        $years = intdiv($months, 12);
        $restMonths = $months % 12;
        $parts = [];

        if ($years > 0) {
            $parts[] = self::formatYears($years);
        }

        if ($restMonths > 0) {
            $parts[] = self::formatMonths($restMonths);
        }

        if (empty($parts)) {
            $parts[] = self::formatMonths($months);
        }

        return implode(' ', $parts);
    }

    public static function splitMonths(?int $months): array
    {
        $months = self::normalizeMonths($months);

        if ($months === null) {
            return [
                'value' => null,
                'unit' => 'years',
            ];
        }

        if ($months !== 0 && $months % 12 === 0) {
            return [
                'value' => intdiv($months, 12),
                'unit' => 'years',
            ];
        }

        return [
            'value' => $months,
            'unit' => 'months',
        ];
    }

    public static function convertInputToMonths(mixed $value, ?string $unit): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $number = (int) $value;

        if ($number < 0) {
            return null;
        }

        return $unit === 'months' ? $number : $number * 12;
    }

    private static function normalizeMonths(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $number = (int) $value;

        return $number >= 0 ? $number : null;
    }

    private static function formatYears(int $years): string
    {
        return $years . ' ' . self::declension($years, 'года', 'лет');
    }

    private static function formatMonths(int $months): string
    {
        return $months . ' ' . self::declension($months, 'месяца', 'месяцев');
    }

    private static function declension(int $value, string $singular, string $plural): string
    {
        $mod100 = $value % 100;

        if ($mod100 >= 11 && $mod100 <= 14) {
            return $plural;
        }

        return $value % 10 === 1 ? $singular : $plural;
    }
}
