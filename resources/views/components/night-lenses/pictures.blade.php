<div class="container px-0 md:px-4 py-5 md:py-10 ">
    @if (!$block->title_hidden)
        <div class="max-w-2xl mx-auto mb-5 md:mb-10">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div class="flex flex-wrap gap-2 px-0 py-6 md:p-7 bg-white md:rounded-20">
        @foreach($block->elements as $index => $item)
            @if(!$item['work'])
            <div class="w-full md:w-[calc(33.33333%-0.4rem)] flex items-center gap-x-3 px-5 md:px-0 md:rounded-2xl" style="background: {{ $item['card_color'] }};">
                @if($index !== 0 && $index !== 5)
                <span class="md:hidden text-5xl text-interactive/20 font-extrabold mr-2">{{ $index }}</span>
                @endif
                <picture
                    itemscope itemtype="http://schema.org/ImageObject"
                    class="[&_img]:w-[160px] [&_img]:min-w-[160px] [&_img]:h-[160px] rounded-full">
                    {{ $item['responsive_image'] }}
                </picture>
                <div>
                    <h3 class="text-heading font-semibold text-sm md:text-base leading-[130%]">{{ $item['title'] }}</h3>
                </div>
                    @if($index !== 0 && $index !== 2 && $index !== 5)
                        <div class="hidden md:block w-14 ml-auto">
                            <x-icon-arrow-long></x-icon-arrow-long>
                        </div>

                    @endif
            </div>
            @endif
        @endforeach
    </div>

    <div class="flex flex-wrap lg:flex-nowrap mt-5 gap-3 px-4 lg:gap-x-7 md:mt-10">
        @foreach($block->elements as $index => $item)
            @if($item['work'])
                <div class="bg-white w-full p-5 lg:w-[calc(33.33333%-0.4rem)] lg:py-4 lg:px-5 rounded-20">
                    <div class="flex items-center gap-5 md:gap-10">
                        <picture
                            itemscope itemtype="http://schema.org/ImageObject"
                            class="[&_img]:w-[60px] [&_img]:min-w-[60px] [&_img]:h-[60px] md:[&_img]:w-[80px] md:[&_img]:min-w-[80px] md:[&_img]:h-[80px] rounded-full">
                            {{ $item['responsive_image'] }}
                        </picture>
                        <h3 class="text-heading font-medium text-xl md:text-2xl">{{ $item['title'] }}</h3>
                    </div>
                    <div class="text-sm md:text-base text-interactive/55 mt-4 md:mt-6">{{ $item['subtitle'] }}</div>
                </div>
            @endif
        @endforeach
    </div>
</div>
