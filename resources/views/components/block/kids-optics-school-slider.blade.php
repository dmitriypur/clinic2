@if($frames !== [])
    <div class="container">
        <h2 class="text-center text-3xl font-semibold leading-tight text-heading md:text-4xl">
            {{ $sliderTitle }}
        </h2>

        <div class="relative left-1/2 mt-8 w-screen -translate-x-1/2 md:left-auto md:mx-auto md:mt-10 md:w-full md:translate-x-0 md:max-w-[1248px]">
            <div
                class="school-frames-swiper swiper overflow-hidden"
                data-school-frames-count="{{ count($frames) }}"
            >
                <div class="swiper-wrapper items-stretch">
                    @foreach($frames as $frame)
                        <div class="swiper-slide flex h-auto !w-72 md:!w-[300px]">
                            @include('components.block.partials.kids-optics-frame-card', [
                                'frame' => $frame,
                                'variant' => 'school-slider',
                            ])
                        </div>
                    @endforeach
                </div>
            </div>

            @if(count($frames) > 1)
                <div class="relative mt-6 flex items-center justify-center gap-6 md:mt-8">
                    <button
                        type="button"
                        class="school-frames-swiper-prev flex size-10 items-center justify-center transition-opacity disabled:cursor-default md:absolute md:-left-12 md:-top-56"
                        aria-label="Предыдущая оправа"
                    >
                        <img src="{{ asset('images/kids-optics/school-slider/arrow.svg') }}" class="h-8 w-5 -scale-x-100" alt="" width="18" height="30">
                    </button>

                    <div class="school-frames-swiper-pagination flex items-center gap-2 [&_.swiper-pagination-bullet]:m-0 [&_.swiper-pagination-bullet]:size-2 [&_.swiper-pagination-bullet]:rounded [&_.swiper-pagination-bullet]:border [&_.swiper-pagination-bullet]:border-heading [&_.swiper-pagination-bullet]:bg-transparent [&_.swiper-pagination-bullet]:opacity-100 [&_.swiper-pagination-bullet.swiper-pagination-bullet-active]:border-2 [&_.swiper-pagination-bullet.swiper-pagination-bullet-active]:border-[#F77C27] [&_.swiper-pagination-bullet.swiper-pagination-bullet-active]:bg-[#F77C27]"></div>

                    <button
                        type="button"
                        class="school-frames-swiper-next flex size-10 items-center justify-center transition-opacity disabled:cursor-default md:absolute md:-right-12 md:-top-56"
                        aria-label="Следующая оправа"
                    >
                        <img src="{{ asset('images/kids-optics/school-slider/arrow.svg') }}" class="h-8 w-5" alt="" width="18" height="30">
                    </button>
                </div>
            @endif
        </div>
    </div>
@endif
