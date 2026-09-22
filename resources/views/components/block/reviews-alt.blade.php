
@php($reviews = $block->reviewsAlt->take(10))

<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto px-10 mb-6 md:mb-12">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

        <div class="reviews-alt-slider">
            <div class="relative">
                <div class="swiper reviews-alt-swiper">
                    <div class="swiper-wrapper">
                        @foreach ($reviews as $review)
                            <div class="swiper-slide mb-2 !h-auto">
                                <x-review-card :block="$block" :review="$review" class="max-w-1/3 gap-10"></x-review-card>
                            </div>
                        @endforeach
                    </div>
                </div>
                @if($reviews->count() > 3)
                    <div class="relative mt-6 flex items-center justify-center gap-6 md:mt-8">
                        <button
                            type="button"
                            class="reviews-alt-swiper-prev flex size-10 items-center justify-center transition-opacity disabled:cursor-default md:absolute md:-left-12 md:-top-56"
                            aria-label="Предыдущий отзыв"
                        >
                            <img src="{{ asset('images/kids-optics/school-slider/arrow.svg') }}" class="h-8 w-5 -scale-x-100" alt="" width="18" height="30">
                        </button>

                        <div class="reviews-alt-swiper-pagination flex items-center gap-2 [&_.swiper-pagination-bullet]:m-0 [&_.swiper-pagination-bullet]:size-2 [&_.swiper-pagination-bullet]:rounded [&_.swiper-pagination-bullet]:border [&_.swiper-pagination-bullet]:border-heading [&_.swiper-pagination-bullet]:bg-transparent [&_.swiper-pagination-bullet]:opacity-100 [&_.swiper-pagination-bullet.swiper-pagination-bullet-active]:border-2 [&_.swiper-pagination-bullet.swiper-pagination-bullet-active]:border-[#F77C27] [&_.swiper-pagination-bullet.swiper-pagination-bullet-active]:bg-[#F77C27]"></div>

                        <button
                            type="button"
                            class="reviews-alt-swiper-next flex size-10 items-center justify-center transition-opacity disabled:cursor-default md:absolute md:-right-12 md:-top-56"
                            aria-label="Следующий отзыв"
                        >
                            <img src="{{ asset('images/kids-optics/school-slider/arrow.svg') }}" class="h-8 w-5" alt="" width="18" height="30">
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex justify-center w-full mt-5 md:mt-10">
            <a href="{{ city_route('review.index') }}"
               class="p-3 md:p-4 text-center btn-gradient font-semibold text-white rounded-lg w-full max-w-[450px] md:text-xl">Смотреть
                все отзывы</a>
        </div>

</div>
