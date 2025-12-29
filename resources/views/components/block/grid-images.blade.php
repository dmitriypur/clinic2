<div class="max-w-2xl mx-auto px-10 mb-6 md:mb-16">
    <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
        {{ $block->title }}
    </h2>
</div>

<div class="flex flex-wrap justify-center items-start gap-4 md:gap-8 md:gap-y-16">
    @foreach($block->images as $item)
        <div
            class="md:w-[calc(33.33333%-1.5rem)] grow flex gap-4 items-center even:flex-row-reverse md:even:flex-row flex-nowrap group">
            <picture class="w-1/2">
                <img src="{{ $block->getFirstMediaUrl($item['uuid'], 'thumb') }}"
                     alt="{{ $item['title'] }}">
            </picture>
            <div class="w-1/2 md:w-2/5 group-even:text-right md:group-even:text-left">
                <h3 class="text-heading font-medium text-lg md:text-xl">
                    {{ $item['title'] }}
                </h3>
            </div>
        </div>
    @endforeach
</div>
