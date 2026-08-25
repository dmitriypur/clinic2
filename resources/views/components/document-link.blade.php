@props([
    'href',
    'label' => 'Открыть документ',
    'ariaLabel' => null,
    'newTab' => true,
    'mobile' => 'button',
    'desktopAt' => 'md',
])

@php
    throw_unless(in_array($mobile, ['button', 'card'], true), InvalidArgumentException::class, 'Unsupported document link mobile mode.');
    throw_unless(in_array($desktopAt, ['md', 'lg'], true), InvalidArgumentException::class, 'Unsupported document link breakpoint.');
@endphp

<a
    href="{{ $href }}"
    @if($newTab) target="_blank" rel="noopener noreferrer" @endif
    aria-label="{{ $ariaLabel ?? $label }}"
    {{ $attributes->class([
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-heading',
        'inline-flex w-full items-center justify-center rounded-xl border border-heading px-7 py-4 text-center text-base font-semibold leading-tight transition-colors hover:bg-heading hover:text-white' => $mobile === 'button',
        'md:w-auto' => $mobile === 'button' && $desktopAt === 'md',
        'lg:w-auto' => $mobile === 'button' && $desktopAt === 'lg',
        'absolute inset-0 rounded-xl' => $mobile === 'card',
        'md:static md:ml-4 md:inline-flex md:shrink-0 md:items-center md:justify-center md:whitespace-nowrap md:border md:border-heading md:px-7 md:py-4 md:text-center md:text-base md:font-semibold md:leading-tight md:transition-colors md:hover:bg-heading md:hover:text-white' => $mobile === 'card' && $desktopAt === 'md',
        'lg:static lg:ml-4 lg:inline-flex lg:shrink-0 lg:items-center lg:justify-center lg:whitespace-nowrap lg:border lg:border-heading lg:px-7 lg:py-4 lg:text-center lg:text-base lg:font-semibold lg:leading-tight lg:transition-colors lg:hover:bg-heading lg:hover:text-white' => $mobile === 'card' && $desktopAt === 'lg',
    ]) }}
>
    <span @class([
        'hidden md:inline' => $mobile === 'card' && $desktopAt === 'md',
        'hidden lg:inline' => $mobile === 'card' && $desktopAt === 'lg',
    ])>{{ $label }}</span>
</a>
