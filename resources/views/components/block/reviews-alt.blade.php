
<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto px-10 mb-6 md:mb-12">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

        <div>
            <div class="relative">
                <div class="swiper reviews-swiper">
                    <div class="swiper-wrapper">
                        @foreach ($block->reviewsAlt as $review)
                            <div class="swiper-slide mb-2 !h-auto">
                                <x-review-card :block="$block" :review="$review" class="max-w-1/3 gap-10"></x-review-card>
                            </div>
                        @endforeach
                    </div>
                </div>
                @if($block->reviewsAlt->count() > 3)
                    <div
                        class="lg:block md:absolute left-0 right-0 xl:-left-12 xl:-right-12 md:top-1/2 md:-translate-y-1/2 mt-6 md:mt-0 z-0">
                        <div class="[&_>_.swiper-button-disabled]:opacity-0 flex justify-center gap-10 md:justify-between">
                            <div
                                class="review-swiper-prev cursor-pointer hover:text-action-primary bg-surface md:bg-transparent [&_svg]:h-5 md:[&_svg]:h-auto flex items-center justify-center w-10 h-10 md:w-auto md:h-auto rounded-full md:p-4 -ml-2">
                                <x-icon-angle-left class="stroke-current fill-none w-5 h-9"/>
                            </div>

                            <div
                                class="review-swiper-next cursor-pointer hover:text-action-primary bg-surface md:bg-transparent [&_svg]:h-5 md:[&_svg]:h-auto flex items-center justify-center w-10 h-10 md:w-auto md:h-auto rounded-full md:p-4 -mr-2">
                                <x-icon-angle-right class="stroke-current fill-none w-5 h-9"/>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            @if($block->reviewsAlt->count() > 3)
                <div
                    class="hidden md:block review-swiper-pagination text-center mt-6 [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued"></div>
            @endif
        </div>

        <div class="flex justify-center w-full mt-5 md:mt-10">
            <a href="{{ city_route('review.index') }}"
               class="p-3 md:p-4 text-center btn-gradient font-semibold text-white rounded-lg w-full max-w-[450px] md:text-xl">Смотреть
                все отзывы</a>
        </div>

</div>
