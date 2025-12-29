@php
    $className = implode(' ', [
        request()->routeIs($route)
        ? 'text-action-primary hover:border-action-primary'
        : 'text-interactive hover:border-interactive',
        'group font-medium my-2 leading-none inline-flex items-center space-x-4'
    ]);

    $textClassName = implode(' ', [
        request()->routeIs($route)
        ? 'group-hover:border-action-primary'
        : 'group-hover:border-interactive',
        'block border-b border-transparent pt-1'
    ]);

    $iconClassName = implode(' ', [
        request()->routeIs($route)
        ? 'fill-action-secondary'
        : 'fill-icon-subdued',
        'inline-flex w-[18px] h-[18px] block',
    ]);
@endphp

<a
    href="{{ city_route($route) }}"
    class="{{ $className }}"
>
    {!! isset($icon) ? '<span class="'.$iconClassName.'">' . $icon . '</span>' : null !!}
    <span class="{{ $textClassName }}">{{ $slot }}</span>
</a>
