@if (!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="container px-8 lg:px-4">
    <div class="grid-carousel-swiper swiper">
        <div class="swiper-wrapper lg:grid grid-cols-3 lg:gap-4">
            @foreach($block->payload['elements'] as $index => $item)
                <div
                    class="swiper-slide flex flex-col items-center lg:gap-4 bg-white lg:bg-transparent border border-action-primary lg:border-none rounded-lg py-4 lg:p-0">
                    <div
                        class="border-2 border-action-primary lg:border-none rounded-full overflow-hidden w-40 lg:w-48">

                        <img
                            src="{{ $block->getFirstMediaUrl($item['media_collection'], 'thumb') }}"
                            alt="{{ $item['title'] }}">
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="text-heading font-medium text-lg md:text-xl leading-none">
                    <span
                        class="box-decoration-clone border-b border-transparent group-hover:border-interactive">{{ $item['title'] }}</span>
                        </h3>
                    </div>
                </div>
            @endforeach
        </div>

        <div
            class="grid-carousel-swiper-pagination text-center mt-4 lg:mt-12 [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued">
        </div>
    </div>
</div>
