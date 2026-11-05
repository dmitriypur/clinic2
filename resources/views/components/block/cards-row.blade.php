@php
    $threeColumns = (int) data_get($block->payload, 'columns', 2) === 3;
    $imageSize = $threeColumns ? 64 : 72;
@endphp

<div class="container">
    @if (!$block->title_hidden)
        <h2 @class([
            'font-semibold text-2xl md:text-4xl text-center text-heading md:mb-10',
            'mb-3' => $threeColumns,
            'mb-5' => !$threeColumns,
        ])>
            {{ $block->title }}
        </h2>
    @endif

    <div @class([
        'gap-2.5',
        'grid grid-cols-1 md:grid-cols-2 md:gap-6 lg:grid-cols-3' => $threeColumns,
        'flex flex-wrap justify-center md:gap-5' => !$threeColumns,
    ])>
        @foreach($items as $index => $item)
            <div
                @class([
                    'flex items-center py-4 bg-white',
                    'gap-2.5 rounded-2xl px-3 md:px-4' => $threeColumns,
                    'w-full md:w-[calc(50%-1rem)] gap-x-3 md:gap-x-8 rounded-xl px-2.5 md:px-6 md:py-3' => !$threeColumns,
                ])
                @if(!isset($item['do_not_show_in_modal']) || !$item['do_not_show_in_modal'])
                    @click="setActiveElementModal('{{ $item['has_extra_info'] }}', '{{ $block->id }}', {{ $index }})"
                @endif
            >
                <picture
                    itemscope itemtype="http://schema.org/ImageObject"
                    @class([
                        'shrink-0',
                        'w-16 h-16' => $threeColumns,
                        'w-18 h-18' => !$threeColumns,
                    ])>
                    @if($item['image'])
                        <img
                            src="{{ $item['image']['src'] }}"
                            @if($item['image']['srcset']) srcset="{{ $item['image']['srcset'] }}" @endif
                            sizes="{{ $imageSize }}px"
                            width="{{ $imageSize }}"
                            height="{{ $imageSize }}"
                            class="w-full h-full"
                            alt="{{ $item['image']['alt'] }}"
                            title="{{ $item['image']['title'] }}"
                            loading="lazy"
                            decoding="async"
                        >
                    @endif
                </picture>
                <p @class([
                    'text-heading',
                    'min-w-0 leading-snug' => $threeColumns,
                    'md:leading-5' => !$threeColumns,
                ])>{{ $item['title'] }}</p>
            </div>
        @endforeach
    </div>
</div>
