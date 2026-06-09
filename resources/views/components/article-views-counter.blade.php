@props([
    'page' => null,
    'count' => null,
    'label' => 'Просмотров',
])

@php
    $value = $count ?? article_views_count($page);
@endphp

<span
    {{ $attributes->class('inline-flex items-center gap-1 text-sm font-semibold') }}
    aria-label="{{ $label }}: {{ number_format((int) $value, 0, '.', ' ') }}"
    title="{{ $label }}"
>
    
    <span>{{ number_format((int) $value, 0, '.', ' ') }}</span>
    <x-icon-eye class="h-5 w-5" aria-hidden="true" />
</span>
