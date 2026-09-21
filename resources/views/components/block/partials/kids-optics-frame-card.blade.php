@php
    $genderClasses = match ($frame['gender']) {
        'boy' => 'border-[#3981f1]/30 bg-[#d8e8ff]/30',
        'girl' => 'border-[#d627cd]/30 bg-[#ffd8f0]/30',
        default => 'border-heading bg-white',
    };
@endphp

<article
    class="overflow-hidden rounded-[24px] bg-white"
    data-frame-catalog-card
    data-frame-catalog-id="{{ $frame['id'] }}"
    data-frame-catalog-ages="{{ implode(',', $frame['ageGroups']) }}"
    data-frame-catalog-genders="{{ implode(',', $frame['genders']) }}"
>
    <div class="h-[230px] overflow-hidden md:h-auto md:aspect-[400/313]">
        <picture>
            @if($frame['image']['avif'])
                <source srcset="{{ $frame['image']['avif'] }}" type="image/avif">
            @endif
            @if($frame['image']['webpSrcset'])
                <source srcset="{{ $frame['image']['webpSrcset'] }}" type="image/webp" sizes="(min-width: 1536px) 400px, (min-width: 768px) calc((100vw - 64px) / 2), calc(100vw - 32px)">
            @elseif($frame['image']['webp'])
                <source srcset="{{ $frame['image']['webp'] }}" type="image/webp">
            @endif
            <img
                src="{{ $frame['image']['src'] }}"
                @if($frame['image']['srcset']) srcset="{{ $frame['image']['srcset'] }}" @endif
                sizes="(min-width: 1536px) 400px, (min-width: 768px) calc((100vw - 64px) / 2), calc(100vw - 32px)"
                class="h-full w-full object-cover"
                alt="{{ $frame['title'] }} — детская оправа"
                width="400"
                height="313"
                loading="lazy"
                decoding="async"
            >
        </picture>
    </div>

    <div class="space-y-5 p-4 md:p-6">
        <div class="space-y-1">
            <h3 class="text-xl font-semibold leading-[1.2] text-heading">
                {{ $frame['title'] }}
            </h3>
            @if($frame['description'] !== '')
                <p class="text-sm font-normal leading-[1.4] text-heading">
                    {{ $frame['description'] }}
                </p>
            @endif
        </div>

        <div class="flex items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-2 text-sm font-normal leading-[1.4] text-heading">
                <span>Цвет:</span>
                <span class="flex items-center gap-1" aria-label="Доступные цвета">
                    @foreach($frame['colors'] as $color)
                        <span class="size-4 rounded-full border border-heading/10" style="background-color: {{ $color }}"></span>
                    @endforeach
                </span>
            </div>

            <span class="shrink-0 rounded-full border px-3 pb-1 pt-1.5 text-center text-xs font-semibold leading-[1.2] text-heading {{ $genderClasses }}">
                {{ $frame['genderLabel'] }}
            </span>
        </div>
    </div>
</article>
