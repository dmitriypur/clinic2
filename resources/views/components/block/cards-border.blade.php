<div class="container">
    @if (!$block->title_hidden)
        <div class="max-w-2xl mx-auto mb-5 md:mb-10">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div
        class="flex flex-wrap gap-4 md:gap-x-10 md:gap-y-5">
        @foreach($block->elements as $index => $item)
            <div
                style="border-color: {{ $item['card_color'] }}"
                class="w-full relative md:w-[calc(50%-1.3rem)] flex md:items-center gap-x-3 md:gap-x-8 rounded-20 py-4 px-2.5 md:p-5 md:min-h-36 bg-white border-4"
                @if(!isset($item['do_not_show_in_modal']) || !$item['do_not_show_in_modal'])
                    @click="setActiveElementModal('{{ $item['has_extra_info'] }}', '{{ $block->id }}', {{ $index }})"
                @endif
            >
                <picture
                    itemscope itemtype="http://schema.org/ImageObject"
                    class="[&_img]:w-[70px] [&_img]:min-w-[70px] [&_img]:md:w-[90px] [&_img]:md:min-w-[90px] rounded-full">
                    {{ $item['responsive_image'] }}
                </picture>
                <div>
                    <h3 class="text-heading leading-tight font-semibold text-lg md:text-sm">{{ $item['title'] }}</h3>
                    <div class="text-sm font-normal mt-3">{!! str($item['body_html'])->sanitizeHtml() !!}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>
