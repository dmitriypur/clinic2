@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-3xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="container">
    <div
        class="flex flex-col md:flex-row gap-10">
        @if($block->hasMedia())
            <div
                class="w-full md:px-4 md:w-1/2 [&_img]:w-full lg:flex-initial lg:shrink-0 overflow-hidden rounded-lg md:rounded-2xl"
                itemscope itemtype="http://schema.org/ImageObject">
                {{ $block->getResponsiveImage('default', $block->title) }}
            </div>
        @endif
        <div class="-ml-4 -mr-4 md:mx-0 md:w-1/2">
            <div class="flex flex-col gap-4">
                @foreach($block->elements as $index => $item)
                    <div class="flex items-center bg-white py-3 px-4 md:px-6 gap-4 md:gap-5 rounded-xl">
                        <div style="background: {{ $item['card_color'] ?? 'red' }};" class="flex items-center justify-center w-18 min-w-18 h-18 rounded-full text-white text-3xl font-medium" >{{ $index + 1 }}</div>
                        <div>
                            <h3 class="text-heading leading-tight font-semibold text-sm">{{ $item['title'] }}</h3>
                            <div class="text-sm font-normal">{!! str($item['body_html'])->sanitizeHtml() !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
