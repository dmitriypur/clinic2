<div class="">

    @if (!$block->title_hidden)
        <div class="mx-auto px-10 mb-6 md:mb-12">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div class="relative">
        <div class="swiper group top-carousel-swiper">
            <div class="group-[:not(.swiper-initialized)]:opacity-0 swiper-wrapper">
                @foreach ($block->images as $item)
                    @if (!$block->hasMedia($item['uuid']))
                        @continue
                    @endif
                    <div class="swiper-slide z-20 group overflow-hidden">
                        <div class="relative opacity-30 group-[.swiper-slide-active]:opacity-100">
                            @if (isset($item['show_callback_button']) && $item['show_callback_button'])
                                <button @click="showCallbackModal(null, 'banner-otpravka-formy')"
                                    onclick="ym(94302729,'reachGoal','banner-call-open')" class="w-full"
                                    aria-label="Записаться на приём">
                                @elseif(isset($item['url']))
                                    <a href="{{ city_route('pages.show', ['handle' => $item['url']]) }}" class="w-full" {{ str_contains($item['url'], "zrenie.clinic") ? '' : 'rel="nofollow"' }}>
                            @endif
                            <picture itemscope itemtype="http://schema.org/ImageObject"
                                class="block justify-center w-full pointer-events-none [&_img]:w-full">
                                <span itemprop="name" class="hidden">{{ $item['title'] }}</span>
                                <span class="{{ $block->hasMedia('mobile_' . $item['uuid']) ? 'hidden ' : '' }}md:block w-full">
                                    {{ $block->getResponsiveImage($item['uuid'], $item['title']) }}
                                </span>
                                @if ($block->hasMedia('mobile_' . $item['uuid']))
                                    <span class="md:hidden">
                                        {{ $block->getResponsiveImage('mobile_' . $item['uuid'], $item['title']) }}
                                    </span>
                                @endif
                            </picture>
                            @if (isset($item['show_callback_button']) && $item['show_callback_button'])
                                </button>
                            @elseif(isset($item['url']))
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @if (count($block->images) > 1)
                {{--                <div --}}
                {{--                    class="hidden lg:block absolute container left-0 right-0 top-1/2 z-10 -translate-y-1/2 "> --}}
                {{--                    <div --}}
                {{--                        class="[&_>_.swiper-button-disabled]:opacity-0 flex justify-between"> --}}
                <div
                    class="hidden lg:block cursor-pointer hover:text-white p-4 -ml-2 absolute top-1/2 -translate-y-1/2 left-1 z-30 [&_>_.swiper-button-disabled]:opacity-0">
                    <div class="promotions-swiper-prev ">
                        <x-icon-angle-left />
                    </div>
                </div>

                <div
                    class="hidden lg:block cursor-pointer hover:text-white p-4 -mr-2 absolute top-1/2 -translate-y-1/2 right-1 z-30 [&_>_.swiper-button-disabled]:opacity-0">
                    <div class="promotions-swiper-next ">
                        <x-icon-angle-right class="stroke-current fill-none w-5 h-9" />
                    </div>
                </div>
                {{--                    </div> --}}
                {{--                </div> --}}
            @endif
        </div>

    </div>
    @if (count($block->images) > 1)
        <div
            class="promotions-swiper-pagination text-center mt-6 lg:mb-6 [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued">
        </div>
    @endif
</div>
