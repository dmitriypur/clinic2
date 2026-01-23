<div class="bg-white px-5 pt-5 rounded-20 relative flex flex-col h-full">
    @if($item['url'] && !isset($block->payload['is_button']))
        <a href="{{ $item['url'] }}" class="absolute inset-0 z-20"></a>
    @endif
    <div class="rounded-xl overflow-hidden relative">
        @if($item['url'] && !isset($block->payload['is_button']))
            <div class="icon-block absolute p-3 bg-white/50 rounded-lg backdrop-blur-sm top-4 right-4 z-10">
                <x-icon-arrow-diagonal></x-icon-arrow-diagonal>
            </div>
        @endif
        <picture itemscope itemtype="http://schema.org/ImageObject"
                 class="block justify-center w-full pointer-events-none [&_img]:w-full">
            <span itemprop="name" class="hidden">{{ $item['title'] }}</span>
            <span class="{{ $block->hasMedia('mobile_' . $item['uuid']) ? 'hidden ' : '' }}md:block w-full">
            {{ $block->getResponsiveImage($item['uuid'], $item['title'], 'main') }}
        </span>
        </picture>
    </div>
    <div class="py-4 flex flex-col justify-center">
        @isset($block->payload['is_button'])
            <span class="text-xl font-semibold">{{ $item['title'] }}</span>
        @else
            <span class="text-center text-xl font-medium [&_span]:block">{!! $block->elementToSpanWrap($item['title']) !!}</span>
        @endisset
    </div>
    @isset($item['text'] )
        <div class="leading-snug mb-10">{{ $item['text'] }}</div>
    @endisset
    @if(!empty($block->payload['is_button']) && $item['url'])
        <div class="mt-auto mb-5 flex justify-center">
           <a href="{{ $item['url'] }}" class="block text-center py-4 px-6 btn-gradient font-semibold text-white rounded-xl w-full !rounded-lg md:py-3">Подробнее</a>
        </div>
    @endif
</div>
