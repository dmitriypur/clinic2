@props([
    'page' => null,
    'count' => null,
    'label' => 'Просмотров',
])

@php
    $value = $count ?? article_views_count($page);
@endphp

<span
    {{ $attributes->class('inline-flex items-center gap-1 text-sm font-medium text-interactive/60') }}
    aria-label="{{ $label }}: {{ number_format((int) $value, 0, '.', ' ') }}"
    title="{{ $label }}"
>
    <x-icon-eye class="h-4 w-4" aria-hidden="true" />
    <span>{{ number_format((int) $value, 0, '.', ' ') }}</span>
</span>
