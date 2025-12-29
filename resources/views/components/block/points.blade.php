@if (!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="container lg:px-4">
    <div class="points-carousel-swiper swiper">
        <div class="swiper-wrapper lg:grid grid-cols-3 lg:gap-4">
            @foreach($block->payload['elements'] as $index => $item)
                <div
                    class="swiper-slide flex flex-col items-start lg:gap-4 lg:bg-transparent rounded-lg py-4">
                    <div class="flex items-center w-full gap-2">
                        <div>
                            <div
                                class="rounded-full w-16 h-16 bg-action-primary flex justify-center items-center font-bold text-white border-8 border-transparent relative after:absolute after:-inset-4 after:rounded-full after:border-r-2 after:border-gray-300">{{ $index + 1 }}</div>
                        </div>
                        @if($index < count($block->payload['elements']) - 1)
                            <div
                                class="w-full bg-gray-300 h-0.5 relative before:size-2 before:-left-1 before:-ml-px before:-mt-px before:-top-0.5 before:absolute before:bg-gray-300 before:rounded-full after:size-2 after:right-0 after:-ml-px after:-mt-px after:-top-0.5 after:absolute after:bg-gray-300 after:rounded-full"></div>
                        @endif
                    </div>
                    <div class="lg:pl-20 lg:-mt-4 mt-6">
                        <h3 class="text-heading font-medium text-lg md:text-xl leading-none">
                    <span
                        class="box-decoration-clone border-b border-transparent group-hover:border-interactive ">{{ $item['title'] }}</span>
                        </h3>
                        <div class="mt-4">{{ $item['body_html'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div
            class="points-carousel-swiper-pagination text-center mt-4 lg:mt-12 [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued">
        </div>
    </div>
</div>
