@push('scripts')
    <script>
        const countCards = "{{ $block->payload['count_visible'] }}";
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
    <div class="relative">
        <div class="swiper cards-swiper">
            <div class="swiper-wrapper">
                @foreach ($block->images as $item)
                    @if (!$block->hasMedia($item['uuid']))
                        @continue
                    @endif
                    <div
                        class=" h-auto swiper-slide">
                        <x-vertical-card :block="$block" :item="$item"></x-vertical-card>
                    </div>
                @endforeach
            </div>
        </div>

        <div
            class="{{ count($block->images) > $block->payload['count_visible'] ? 'lg:block' : 'lg:hidden' }} md:absolute left-0 right-0 xl:-left-12 xl:-right-12 md:top-1/2 md:-translate-y-1/2 mt-6 md:mt-0 z-0">
            <div class="[&_>_.swiper-button-disabled]:opacity-0 flex justify-center gap-10 md:justify-between">
                <div
                    class="cards-swiper-prev cursor-pointer hover:text-action-primary bg-surface md:bg-transparent [&_svg]:h-5 md:[&_svg]:h-auto flex items-center justify-center w-10 h-10 md:w-auto md:h-auto rounded-full md:p-4 -ml-2">
                    <x-icon-angle-left class="stroke-current fill-none w-5 h-9"/>
                </div>

                <div
                    class="cards-swiper-next cursor-pointer hover:text-action-primary bg-surface md:bg-transparent [&_svg]:h-5 md:[&_svg]:h-auto flex items-center justify-center w-10 h-10 md:w-auto md:h-auto rounded-full md:p-4 -mr-2">
                    <x-icon-angle-right class="stroke-current fill-none w-5 h-9"/>
                </div>
            </div>
        </div>
    </div>
    <div
        class="hidden {{ count($block->images) > $block->payload['count_visible'] ? 'md:block' : 'md:hidden' }} cards-swiper-pagination text-center mt-4 lg:mt-10 [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued">
    </div>
</div>
