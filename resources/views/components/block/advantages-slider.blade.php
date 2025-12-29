@push('scripts')
    <script>
        const countCards = "{{ $block->payload['count_visible'] }}";
    </script>
@endpush
@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="container">
    <div class="relative">
        <div class="swiper1 advantages-swiper1">
            <div class="swiper-wrapper1 grid md:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($block->images as $item)
                    @if (!$block->hasMedia($item['uuid']))
                        @continue
                    @endif
                    <div
                        class="h-auto swiper-slide">
                        <div class="bg-white py-4 pl-4 pr-8 md:p-3 rounded-2xl relative flex flex-col h-full">
                            @isset($item['url'])
                                <a href="{{ city_route('pages.show', ['handle' => $item['url']]) }}" class="absolute inset-0 z-20"></a>
                            @endisset

                            <div class="grid grid-cols-3 md:flex flex-col">
                                <div class="col-span-1 rounded-xl overflow-hidden relative md:mb-5">
                                    <picture itemscope itemtype="http://schema.org/ImageObject"
                                             class="block justify-center w-full pointer-events-none [&_img]:w-full">
                                        <span itemprop="name" class="hidden">{{ $item['title'] }}</span>
                                        <span class="{{ $block->hasMedia('mobile_' . $item['uuid']) ? 'hidden ' : '' }}md:block w-full">
                                        {{ $block->getResponsiveImage($item['uuid'], $item['title'], 'main') }}
                                    </span>
                                    </picture>
                                </div>
                                <h3 class="col-span-2 ml-3 md:m-0 self-center text-lg/5 font-medium [&_span]:block">{!! $block->elementToSpanWrap($item['title']) !!}</h3>
                                <div class="col-span-3 text-sm/4 text-interactive/50 mt-2">{{ $item['text'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div
            class="flex items-center justify-center gap-x-9 mt-4 hidden">
            <div
                class="advantages-swiper-prev cursor-pointer hover:opacity-60 bg-surface [&_svg]:h-3.5 flex items-center justify-center w-8 h-8 rounded-full -ml-2">
                <x-icon-angle-left class="stroke-current fill-none w-5 h-9"/>
            </div>

            <div
                class="advantages-swiper-next cursor-pointer hover:opacity-60 bg-surface [&_svg]:h-3.5 flex items-center justify-center w-8 h-8 rounded-full -mr-2">
                <x-icon-angle-right class="stroke-current fill-none w-5 h-9"/>
            </div>
        </div>
    </div>
</div>
