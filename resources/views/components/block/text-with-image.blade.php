<div
    class="flex flex-col-reverse {{ isset($block->payload['reverse']) && $block->payload['reverse'] ? 'lg:flex-row-reverse md:py-7 md:px-16' : 'lg:flex-row gap-9 md:p-7' }} px-4 py-6 bg-white rounded-xl md:rounded-20 relative">
    @if(!empty($block->payload['link']))
        <a href="{{ city_route('pages.show', ['handle' => $block->payload['link']]) }}" class="absolute inset-0 z-10"></a>
    @endif
    @if($block->payload['add_fox'])
        <img src="{{ asset('images/fireworks.webp') }}" alt="Веселый корги"
             width="167" height="224"
             class="absolute hidden md:block md:-top-12 md:right-0 md:w-[167px]">
    @endif

    @if($block->hasMedia())
        <div
            class="mt-8 lg:mt-0 lg:max-w-[400px] w-full [&_img]:w-full [&_img]:h-full [&_img]:object-cover lg:flex-initial lg:shrink-0 overflow-hidden rounded-lg md:rounded-2xl"
            itemscope itemtype="http://schema.org/ImageObject">
            <span itemprop="name" class="hidden">{{ $block->title  }}</span>
            {{ $block->getResponsiveImage('default', $block->title) }}
            <span itemprop="description" class="hidden">{{ $block->title }}</span>
        </div>
    @endif
    <div class="w-auto {{ isset($block->payload['reverse']) ? 'md:pr-12' : 'md:pr-24' }}">
        @if(!$block->title_hidden)
            <h2 class="font-semibold text-2xl md:text-2xl text-left text-heading">
                {{ $block->title }}
            </h2>
        @endif

        <div
            class="content [&_strong]:text-interactive [&_li]:marker:text-interactive {{ isset($block->payload['reverse']) ? 'mt-2' : 'mt-4' }}">
            {!! $block->body_html !!}
        </div>
    </div>
</div>
