@props([
    'page' => null,
    'count' => null,
    'label' => 'Записей на диагностику',
])

@php
    $value = $count ?? article_booking_count($page);
@endphp

<span {{ $attributes->class('flex items-center gap-1 font-medium text-interactive/50') }}>
    <span>{{ $label }}:</span>
    <span>{{ number_format((int) $value, 0, '.', ' ') }}</span>
</span>
