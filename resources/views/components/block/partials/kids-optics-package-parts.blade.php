@props([
    'variant' => 'basic',
])

@php
    $premium = $variant === 'premium';
    $itemClasses = $premium
        ? 'border-white bg-[#f77c27] text-white md:border-transparent md:bg-white/20'
        : 'border-[#808eae]/30 bg-white/20 text-heading';
    $plusIcon = $premium ? 'plus-white.svg' : 'plus-blue.svg';
@endphp

<div {{ $attributes->class(['absolute left-4 right-4 z-20 flex flex-col items-center gap-1 md:left-7 md:right-auto md:flex-row']) }}>
    @foreach([
        ['icon' => 'frame.svg', 'label' => 'Оправа'],
        ['icon' => 'lenses.svg', 'label' => 'Линзы'],
        ['icon' => 'manufacturing.svg', 'label' => 'Изготовление'],
    ] as $part)
        <div class="flex w-full items-center justify-center gap-1 rounded-xl border px-2 py-1.5 text-sm font-bold leading-snug md:w-auto {{ $itemClasses }}">
            <span
                aria-hidden="true"
                class="size-6 shrink-0 bg-current"
                style="mask: url('/images/kids-optics/packages/{{ $part['icon'] }}') center / contain no-repeat; -webkit-mask: url('/images/kids-optics/packages/{{ $part['icon'] }}') center / contain no-repeat;"
            ></span>
            <span>{{ $part['label'] }}</span>
        </div>

        @unless($loop->last)
            <img src="/images/kids-optics/packages/{{ $plusIcon }}" alt="" class="size-2 shrink-0" width="8" height="8">
        @endunless
    @endforeach
</div>
