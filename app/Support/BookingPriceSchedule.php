<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/** Price periods are authoritative only for keys explicitly managed on the site. */
class BookingPriceSchedule
{
    public const FIELDS = ['price', 'price_child'];

    public static function normalize(mixed $value): ?string
    {
        $value = $value === null ? '' : trim((string) $value);

        return $value === '' ? null : $value;
    }

    public static function fingerprint(array $owner, string $field): string
    {
        return hash('sha256', json_encode([
            $owner[$field] ?? null,
            $owner['price_periods'][$field] ?? null,
        ], JSON_THROW_ON_ERROR));
    }

    public static function price(array $owner, string $field, ?string $date = null): ?string
    {
        if (! array_key_exists($field, $owner['price_periods'] ?? [])) {
            return self::normalize($owner[$field] ?? null);
        }

        $date ??= now()->toDateString();
        $period = self::period($owner, $field, $date);

        if ($period === null || (filled($period['ends_on'] ?? null) && $period['ends_on'] < $date)) {
            return null;
        }

        return self::normalize($period['price'] ?? null);
    }

    private static function period(array $owner, string $field, string $date): ?array
    {
        $period = null;

        foreach ($owner['price_periods'][$field] ?? [] as $candidate) {
            $start = $candidate['starts_on'] ?? null;

            if (($start === null || $start <= $date)
                && ($period === null || ($start ?? '') >= ($period['starts_on'] ?? ''))) {
                $period = $candidate;
            }
        }

        return $period;
    }

    public static function pending(array $owner, string $field): ?array
    {
        return self::future($owner, $field)[0] ?? null;
    }

    private static function future(array $owner, string $field): array
    {
        return collect($owner['price_periods'][$field] ?? [])
            ->filter(fn (array $period): bool => filled($period['starts_on'] ?? null) && $period['starts_on'] > now()->toDateString())
            ->sortBy('starts_on')
            ->values()->all();
    }

    public static function hydrate(array $owner): array
    {
        foreach (self::FIELDS as $field) {
            $current = self::price($owner, $field);
            $currentEnd = self::period($owner, $field, now()->toDateString())['ends_on'] ?? null;
            $owner['_booking_price_editor'][$field] = [
                'amount' => $current,
                'original_amount' => $current,
                'current_ends_on' => $currentEnd,
                'snapshot' => self::fingerprint($owner, $field),
                'starts_on' => now()->toDateString(),
                'ends_on' => null,
                'scheduled' => self::future($owner, $field),
                'original_scheduled' => self::future($owner, $field),
                'action' => null,
            ];
        }

        return $owner;
    }

    public static function save(array $data, array $original, string $errorPrefix = 'data', bool $legacyDoctorFallback = false, bool $legacyApiFallback = false): array
    {
        $editors = $data['_booking_price_editor'] ?? [];
        unset($data['_booking_price_editor']);

        // The stored values, not hidden form inputs, are the source of truth.
        foreach (self::FIELDS as $field) {
            unset($data[$field]);
            if (array_key_exists($field, $original)) {
                $data[$field] = $original[$field];
            }
        }
        unset($data['price_periods']);
        if (array_key_exists('price_periods', $original)) {
            $data['price_periods'] = $original['price_periods'];
        }

        foreach (self::FIELDS as $field) {
            $editor = $editors[$field] ?? [];
            $amount = self::normalize($editor['amount'] ?? null);
            $action = $editor['action'] ?? null;
            $scheduled = $editor['scheduled'] ?? [];
            $scheduleChanged = self::scheduleValues($scheduled) !== self::scheduleValues($editor['original_scheduled'] ?? []);
            $primaryChanged = $action === 'edit_period' || $amount !== self::normalize($editor['original_amount'] ?? null);

            if ($action === null && ! $primaryChanged && ! $scheduleChanged) {
                continue;
            }

            $fail = fn (string $key, string $message) => throw ValidationException::withMessages([
                "{$errorPrefix}._booking_price_editor.{$field}.{$key}" => $message,
            ]);

            if (($editor['snapshot'] ?? null) !== self::fingerprint($original, $field)) {
                $fail('amount', 'Цена уже изменена в другой форме. Обновите страницу и повторите изменение.');
            }

            if (! in_array($action, [null, 'clear', 'edit_period'], true)) {
                $fail('amount', 'Неизвестное действие с ценой. Обновите страницу.');
            }

            $baseline = self::normalize($original[$field] ?? null);
            if ($baseline === null && $legacyDoctorFallback) {
                $baseline = self::price($original, $field === 'price' ? 'price_child' : 'price');
            }
            $periods = $original['price_periods'][$field] ?? [[
                'price' => $baseline,
                'starts_on' => null,
                'ends_on' => null,
            ]];
            // An untouched branch may currently display an API price. Keep that
            // source only in the baseline, before the first local period starts.
            if ($legacyApiFallback && $baseline === null && ! array_key_exists($field, $original['price_periods'] ?? [])) {
                $periods[0]['use_api'] = true;
            }
            $today = now()->toDateString();

            if (($primaryChanged && $amount === null) || $action === 'clear') {
                $periods = array_values(array_filter($periods,
                    fn (array $period): bool => ($period['starts_on'] ?? '') < $today));
                $periods[] = ['price' => null, 'starts_on' => $today, 'ends_on' => null];
                $data['price_periods'][$field] = $periods;
                $data[$field] = null;

                continue;
            }

            $future = $scheduleChanged ? $scheduled : self::future($original, $field);
            $replacements = [];
            foreach ($future as $key => $row) {
                // Preserve keys until validation so repeater errors target the row.
                $rowFail = fn (string $name, string $message) => $fail("scheduled.{$key}.{$name}", $message);
                self::validatePeriod($row['price'] ?? null, $row['starts_on'] ?? null, $row['ends_on'] ?? null, $today, $rowFail);
                if (isset($replacements[$row['starts_on']])) {
                    $rowFail('starts_on', 'На этот день уже задана цена. Для каждой даты начала оставьте одну цену.');
                }
                $replacements[$row['starts_on']] = [
                    'price' => self::normalize($row['price']),
                    'starts_on' => $row['starts_on'],
                    'ends_on' => self::normalize($row['ends_on'] ?? null),
                ];
            }

            if ($primaryChanged) {
                $start = $editor['starts_on'] ?? null;
                $end = self::normalize($editor['ends_on'] ?? null);
                self::validatePeriod($amount, $start, $end, $today, fn ($name, $message) => $fail($name === 'price' ? 'amount' : $name, $message));
                if (isset($replacements[$start])) {
                    $fail('starts_on', 'На этот день уже задана цена в расписании. Измените её в соответствующей строке.');
                }
                $replacements[$start] = ['price' => $amount, 'starts_on' => $start, 'ends_on' => $end];
            }

            $periods = array_values(array_filter($periods, fn (array $period): bool => ($period['starts_on'] ?? '') <= $today && ! isset($replacements[$period['starts_on'] ?? ''])));
            $periods = array_merge($periods, array_values($replacements));
            usort($periods, fn (array $a, array $b): int => ($a['starts_on'] ?? '') <=> ($b['starts_on'] ?? ''));
            $data['price_periods'][$field] = $periods;
        }

        return $data;
    }

    private static function scheduleValues(array $rows): array
    {
        $rows = array_map(fn (array $row): array => [
            'price' => self::normalize($row['price'] ?? null),
            'starts_on' => $row['starts_on'] ?? null,
            'ends_on' => self::normalize($row['ends_on'] ?? null),
        ], array_values($rows));
        usort($rows, fn (array $a, array $b): int => ($a['starts_on'] ?? '') <=> ($b['starts_on'] ?? ''));

        return $rows;
    }

    private static function validatePeriod(mixed $amount, mixed $start, mixed $end, string $today, callable $fail): void
    {
        $amount = self::normalize($amount);
        $end = self::normalize($end);
        if ($amount === null || ! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount) || (float) $amount <= 0) {
            $fail('price', 'Укажите положительную сумму в рублях, до двух знаков после точки.');
        }
        if (! self::validDate($start) || $start < $today) {
            $fail('starts_on', 'Укажите дату начала не раньше сегодняшнего дня.');
        }
        if ($end !== null && (! self::validDate($end) || $end < $start)) {
            $fail('ends_on', 'Дата окончания должна быть не раньше даты начала.');
        }
    }

    private static function validDate(mixed $value): bool
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) {
            return false;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }
}
