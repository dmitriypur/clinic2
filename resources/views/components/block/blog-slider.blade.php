@push('scripts')
    <script>
        const countBlog = "{{ $block->payload['count_visible'] }}";
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
    <div class="relative lg:px-10">
        <div class="swiper blog-swiper">
            <div class="swiper-wrapper">
                @foreach ($block->posts as $item)
                    <div class="swiper-slide h-auto">
                        <x-blog-card :item="$item"></x-blog-card>
                    </div>
                @endforeach
            </div>
        </div>
        <div
            class="lg:block md:absolute left-0 right-0 xl:-left-12 xl:-right-12 md:top-1/2 md:-translate-y-1/2 mt-6 md:mt-0 z-0">
            <div class="[&_>_.swiper-button-disabled]:opacity-0 flex justify-center gap-10 md:justify-between">
                <div
                    class="blog-swiper-prev cursor-pointer hover:text-action-primary bg-surface md:bg-transparent [&_svg]:h-5 md:[&_svg]:h-auto flex items-center justify-center w-10 h-10 md:w-auto md:h-auto rounded-full md:p-4 -ml-2">
                    <x-icon-angle-left class="stroke-current fill-none w-5 h-9"/>
                </div>

                <div
                    class="blog-swiper-next cursor-pointer hover:text-action-primary bg-surface md:bg-transparent [&_svg]:h-5 md:[&_svg]:h-auto flex items-center justify-center w-10 h-10 md:w-auto md:h-auto rounded-full md:p-4 -mr-2">
                    <x-icon-angle-right class="stroke-current fill-none w-5 h-9"/>
                </div>
            </div>
        </div>
    </div>
    <div class="relative">
        <a href="{{ city_route('stati.index') }}" class="flex items-center justify-center py-3 px-6 orange-gr font-semibold text-white rounded-lg w-full max-w-80 mx-auto mt-6 text-lg md:m-0 md:p-0 md:text-base md:block md:bg-none md:hover:bg-none md:text-action-primary md:absolute md:underline">Все статьи</a>
        <div
            class="hidden md:block blog-swiper-pagination text-center mt-6 [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued"></div>
    </div>

</div>
