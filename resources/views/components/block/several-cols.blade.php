@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-3xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="bg-white md:container md:rounded-20 px-11 py-8 md:px-28 relative">
    <div class="stories-swiper swiper">
        <div class="swiper-wrapper">
            @foreach($block->payload['elements'] as $key => $value)
                <div class="swiper-slide">
                    <div>
                        <div class="md:flex items-center gap-20 font-medium italic mb-4">
                            <div class="text-xl md:text-2xl md:w-1/2 [&_span]:text-interactive/50">
                                {!! $value['title'] !!}
                            </div>
                            <div class="hidden md:block md:w-1/2">
                                {!! $value['subtitle'] !!}
                            </div>
                        </div>
                        <faq inline-template>
                            <div>
                                <div
                                    :class="['overflow-hidden md:max-h-full md:text-sm text-interactive/50 md:columns-2 gap-x-20', openAll ? 'max-h-full' : 'max-h-80']">
                                    {!! $value['body_html'] !!}
                                </div>

                                <div class="md:hidden italic mt-4 text-center font-medium">
                                    {!! $value['subtitle'] !!}
                                </div>
                                <x-button-primary class="block mx-auto mt-5 w-60 md:hidden" @click.self="toggleAll">
                                    @{{ openAll ? 'Скрыть' : 'Читать полностью' }}
                                </x-button-primary>
                            </div>
                        </faq>
                    </div>
                </div>
            @endforeach
        </div>
        <div
            class="relative z-20 stories-swiper-pagination text-center mt-4 md:mt-8 [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued">
        </div>
    </div>
    <div
        class="absolute [&_>_.swiper-button-disabled]:opacity-0 left-2 lg:left-10 -translate-y-1/2 top-1/2 z-10">
        <div
            class="stories-swiper-prev cursor-pointer hover:text-action-primary">
            <x-icon-angle-left class="stroke-current fill-none w-5 h-9"/>
        </div>
    </div>

    <div
        class="absolute [&_>_.swiper-button-disabled]:opacity-0 right-2 lg:right-10 -translate-y-1/2 top-1/2 z-10">
        <div
            class="stories-swiper-next cursor-pointer hover:text-action-primary">
            <x-icon-angle-right class="stroke-current fill-none w-5 h-9"/>
        </div>
    </div>
</div>

