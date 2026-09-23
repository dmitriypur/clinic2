<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto px-10 mb-6 md:mb-12">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif
    <div class="relative">
        <div class="swiper promotions-swiper">
            <div class="swiper-wrapper">
                @foreach($promotions as $item)
                    @php
                        $desktop = $item->getFirstMedia('default');
                        $mobile = $item->getFirstMedia('block_mobile') ?? $desktop;
                    @endphp
                    @if(!$desktop)
                        @continue
                    @endif
                    @php
                        $desktopWebp = \App\Support\PromotionImageSource::webpSrcset($desktop);
                        $mobileWebp = \App\Support\PromotionImageSource::webpSrcset($mobile);
                    @endphp
                    <div class="swiper-slide group rounded-3xl overflow-hidden relative" itemscope itemtype="http://schema.org/ImageObject">
                        <a href="{{ $item->description_html }}" aria-label="Переход к акции: {{ $item->title }}" class="absolute inset-0 z-10"></a>
                        <span itemprop="name" class="hidden">{{ $item->title }}</span>
                        <picture class="block">
                            @if($desktopWebp)
                                <source media="(min-width: 768px)" type="image/webp" srcset="{{ $desktopWebp }}" sizes="(min-width: 1280px) 593px, 46vw">
                            @endif
                            <source media="(min-width: 768px)" srcset="{{ $desktop->getUrl() }}">
                            @if($mobileWebp)
                                <source media="(max-width: 767px)" type="image/webp" srcset="{{ $mobileWebp }}" sizes="100vw">
                            @endif
                            <img src="{{ $mobile->getUrl() }}" alt="{{ $item->title }}" title="{{ $item->title }} фото"
                                 width="361" height="361" loading="lazy" decoding="async"
                                 class="block w-full h-auto aspect-[auto_1/1] md:aspect-[auto_593/263]">
                        </picture>
                    </div>
                @endforeach

            </div>

        </div>
        <div
            class="lg:block md:absolute left-0 right-0 xl:-left-12 xl:-right-12 md:top-1/2 md:-translate-y-1/2 mt-6 md:mt-0 z-0">
            <div class="[&_>_.swiper-button-disabled]:opacity-0 flex justify-center gap-10 md:justify-between">
                <div
                    class="promotions-swiper-prev cursor-pointer hover:text-action-primary bg-surface md:bg-transparent [&_svg]:h-5 md:[&_svg]:h-auto flex items-center justify-center w-10 h-10 md:w-auto md:h-auto rounded-full md:p-4 -ml-2">
                    <x-icon-angle-left class="stroke-current fill-none w-5 h-9"/>
                </div>

                <div
                    class="promotions-swiper-next cursor-pointer hover:text-action-primary bg-surface md:bg-transparent [&_svg]:h-5 md:[&_svg]:h-auto flex items-center justify-center w-10 h-10 md:w-auto md:h-auto rounded-full md:p-4 -mr-2">
                    <x-icon-angle-right class="stroke-current fill-none w-5 h-9"/>
                </div>
            </div>
        </div>
    </div>
    <div
        class="hidden md:block promotions-swiper-pagination text-center mt-6 [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued"></div>
</div>
