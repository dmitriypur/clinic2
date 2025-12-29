<div class="bg-white px-4 pt-4 rounded-20 relative flex flex-col h-full">
    @if($item['url'])
        <a href="{{ city_route('pages.show', ['handle' => $item['url']]) }}" class="absolute inset-0 z-20"></a>
    @endif
    <div class="rounded-xl overflow-hidden relative">
        <div class="icon-block absolute p-3 bg-white/50 rounded-lg backdrop-blur-sm top-4 right-4 z-10">
            <x-icon-arrow-diagonal></x-icon-arrow-diagonal>
        </div>
        <picture itemscope itemtype="http://schema.org/ImageObject"
                 class="block justify-center w-full pointer-events-none [&_img]:w-full">
            <span itemprop="name" class="hidden">{{ $item['title'] }}</span>
            <span class="{{ $block->hasMedia('mobile_' . $item['uuid']) ? 'hidden ' : '' }}md:block w-full">
            {{ $block->getResponsiveImage($item['uuid'], $item['title'], 'main') }}
        </span>
        </picture>
    </div>
    <div class="py-4 grow flex flex-col justify-center">
        <span class="text-center text-xl font-medium [&_span]:block">{!! $block->elementToSpanWrap($item['title']) !!}</span>
    </div>
</div>
