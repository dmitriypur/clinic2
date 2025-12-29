<div class="container">
    @if (!$block->title_hidden)
        <div class="max-w-2xl mx-auto mb-5 md:mb-10">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div
        class="flex flex-wrap gap-4 md:gap-x-8 md:gap-y-2.5">
        @foreach($block->elements as $index => $item)
            <div
                class="w-full md:w-[calc(50%-1rem)] flex items-center gap-x-3 md:gap-x-8 rounded-xl py-4 px-2.5 md:px-6 md:py-3 bg-white"
                @if(!isset($item['do_not_show_in_modal']) || !$item['do_not_show_in_modal'])
                    @click="setActiveElementModal('{{ $item['has_extra_info'] }}', '{{ $block->id }}', {{ $index }})"
                @endif
            >
                <picture
                    itemscope itemtype="http://schema.org/ImageObject"
                    class="[&_img]:w-[70px] [&_img]:min-w-[70px] [&_img]:h-[70px] rounded-full">
                    {{ $item['responsive_image'] }}
                </picture>
                <div>
                    <h3 class="text-heading font-normal text-sm md:text-lg md:leading-none">{{ $item['title'] }}</h3>
                </div>
            </div>
        @endforeach
    </div>
</div>
