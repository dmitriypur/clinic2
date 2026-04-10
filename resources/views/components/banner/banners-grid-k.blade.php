<div class="w-full overflow-hidden relative ">
    <div class="container relative">
        <div class="banner-grid-swiper swiper">
            <div class="swiper-wrapper md:grid md:grid-cols-11 md:grid-rows-2 md:gap-x-6 md:gap-y-10">

                <div class="swiper-slide md:col-span-6 row-span-2 rounded-xl lg:rounded-[30px] relative overflow-hidden">
                    <span itemprop="name" class="hidden">{{ $block->images[0]['title'] }}</span>
                    <picture itemscope itemtype="http://schema.org/ImageObject"
                             class="block justify-center w-full h-full pointer-events-none">
                        @if ($block->hasMedia('mobile_' . $block->images[0]['uuid']))
                            <source srcset="{{ $block->getImageUrl('mobile_' . $block->images[0]['uuid']) }}" media="(max-width: 767px)">
                        @endif
                        <img
                            src="{{ $block->getImageUrl($block->images[0]['uuid']) }}"
                            alt="{{ $block->getImageAltText($block->images[0]['title']) }}"
                            title="{{ $block->getImageTitleText($block->images[0]['title']) }}"
                            itemprop="contentUrl"
                            class="w-full h-full"
                        >
                    </picture>
                    @if($block->images[0]['url'])
                        <div class="px-6 md:p-0 absolute left-0 w-full md:w-auto bottom-6 md:bottom-[10%] md:left-[7%] lg:bottom-[12%]">
                            <a href="{{ city_url($block->images[0]['url']) }}" class="block text-center py-4 btn-blue-gradient font-semibold text-white rounded w-full rounded-lg md:px-14 text-sm">Записаться на прием</a>
                        </div>
                    @else
                        <div class="px-6 md:p-0 absolute left-0 w-full md:w-auto bottom-6 md:bottom-[10%] md:left-[7%] lg:bottom-[12%]">
                            <button @click="showCallbackModal(null, 'otpravka-formy')" class="block text-center py-4 btn-blue-gradient font-semibold text-white rounded w-full rounded-lg md:px-14 text-sm">Записаться на прием</button>
                        </div>
                    @endif
                </div>
                <div class="swiper-slide md:col-span-5 row-span-1 relative rounded-xl lg:rounded-20 overflow-hidden">
                    <span itemprop="name" class="hidden">{{ $block->images[1]['title'] }}</span>
                    <picture itemscope itemtype="http://schema.org/ImageObject"
                             class="block justify-center w-full h-full pointer-events-none">
                        @if ($block->hasMedia('mobile_' . $block->images[1]['uuid']))
                            <source srcset="{{ $block->getImageUrl('mobile_' . $block->images[1]['uuid']) }}" media="(max-width: 767px)">
                        @endif
                        <img
                            src="{{ $block->getImageUrl($block->images[1]['uuid']) }}"
                            alt="{{ $block->getImageAltText($block->images[1]['title']) }}"
                            title="{{ $block->getImageTitleText($block->images[1]['title']) }}"
                            itemprop="contentUrl"
                            class="w-full h-full"
                        >
                    </picture>
                    @if($block->images[1]['url'])
                        <div class="px-4 md:p-0 absolute w-full md:w-auto left-0 bottom-6 md:left-[6%] md:bottom-[20%]">
                            <a href="{{ city_url($block->images[1]['url']) }}" class="block text-center py-4 md:py-3.5 px-12 btn-blue-gradient font-semibold text-white rounded-lg w-full text-sm">Записаться на прием</a>
                        </div>
                    @else
                        <div class="px-4 md:p-0 absolute w-full md:w-auto left-0 bottom-6 md:left-[6%] md:bottom-[20%]">
                            <button @click="showCallbackModal(null, 'otpravka-formy')" class="block text-center py-4 md:py-3.5 px-12 btn-blue-gradient font-semibold text-white rounded-lg w-full text-sm">Записаться на прием</button>
                        </div>
                    @endif
                </div>
                <div class="swiper-slide md:col-span-5 row-span-1 relative rounded-xl lg:rounded-20 overflow-hidden">
                    <span itemprop="name" class="hidden">{{ $block->images[2]['title'] }}</span>
                    <picture itemscope itemtype="http://schema.org/ImageObject"
                             class="block justify-center w-full h-full pointer-events-none">
                        @if ($block->hasMedia('mobile_' . $block->images[2]['uuid']))
                            <source srcset="{{ $block->getImageUrl('mobile_' . $block->images[2]['uuid']) }}" media="(max-width: 767px)">
                        @endif
                        <img
                            src="{{ $block->getImageUrl($block->images[2]['uuid']) }}"
                            alt="{{ $block->getImageAltText($block->images[2]['title']) }}"
                            title="{{ $block->getImageTitleText($block->images[2]['title']) }}"
                            itemprop="contentUrl"
                            class="w-full h-full"
                        >
                    </picture>
                    @if($block->images[2]['url'])
                        <div class="px-4 md:p-0 absolute w-full md:w-auto left-0 bottom-6 md:left-[6%] md:bottom-[20%]">
                            <a href="{{ city_url($block->images[1]['url']) }}" class="block text-center py-4 md:py-3.5 px-12 btn-blue-gradient font-semibold text-white rounded-lg w-full text-sm">Записаться на прием</a>
                        </div>
                    @else
                        <div class="px-4 md:p-0 absolute w-full md:w-auto left-0 bottom-6 md:left-[6%] md:bottom-[20%]">
                            <button @click="showCallbackModal(null, 'otpravka-formy')" class="block text-center py-4 md:py-3.5 px-12 btn-blue-gradient font-semibold text-white rounded-lg w-full text-sm">Записаться на прием</button>
                        </div>
                    @endif
                </div>

            </div>
            <div class="flex items-center justify-center gap-x-9 mt-4 md:hidden">
                <div
                    class="banner-grid-swiper-prev cursor-pointer hover:opacity-60 bg-surface md:bg-transparent [&_svg]:h-3.5 flex items-center justify-center w-8 h-8 rounded-full -ml-2">
                    <x-icon-angle-left class="stroke-current fill-none w-5 h-9"/>
                </div>

                <div
                    class="banner-grid-swiper-next cursor-pointer hover:opacity-60 bg-surface md:bg-transparent [&_svg]:h-3.5 flex items-center justify-center w-8 h-8 rounded-full -mr-2">
                    <x-icon-angle-right class="stroke-current fill-none w-5 h-9"/>
                </div>
            </div>
        </div>
    </div>
</div>
