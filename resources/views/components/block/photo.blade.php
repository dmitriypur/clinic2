<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto px-10 mb-6 md:mb-12">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div class="-mx-4 lg:mx-0">
        <div class="relative">
            <div class="swiper gallery-swiper">
                <div class="swiper-wrapper">
                    @foreach ($block->images as $item)
                        @if (!$block->hasMedia($item['uuid']))
                            @continue
                        @endif
                        <div
                            class="swiper-slide mb-2 !h-auto rounded-lg overflow-hidden z-[200] relative">
                            <a href="{{ $block->getImageUrl($item['uuid']) }}"
                               class="glightbox inline-block cursor-pointer">
                                <picture itemscope
                                         itemtype="http://schema.org/ImageObject"
                                         class="flex justify-center w-full">
                                <span itemprop="name"
                                      class="hidden">{{ $item['title'] }}</span>
                                    {{ $block->getResponsiveImage($item['uuid'], $item['title']) }}
                                </picture>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            <div
                class="[&_>_.swiper-button-disabled]:opacity-0 hidden lg:block">
                <div
                    class="gallery-swiper-prev absolute right-auto -left-12 top-1/2 -translate-y-1/2 z-10 cursor-pointer hover:text-action-primary">
                    <x-icon-angle-left class="stroke-current fill-none w-5 h-9"/>
                </div>

                <div
                    class="gallery-swiper-next absolute left-auto -right-12 top-1/2 -translate-y-1/2 z-10 cursor-pointer hover:text-action-primary">
                    <x-icon-angle-right class="stroke-current fill-none w-5 h-9"/>
                </div>
            </div>
        </div>
        <div
            class="gallery-swiper-pagination text-center mt-4 lg:mt-12 [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued">
        </div>
    </div>
</div>
