<div class="container pr-0 pb-5 md:pb-10 md:px-4">
    @if (!$block->title_hidden)
        <div class="max-w-2xl mx-auto mb-5 md:mb-10">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    @if(!empty($block->payload['is_slider']))
        <div class="night-lenses-selection-swiper swiper relative">
            <div class="swiper-wrapper md:grid md:grid-cols-2 lg:grid-cols-{{ isset($block->payload['count_column']) ? $block->payload['count_column'] : 3 }} mt-5 md:gap-6 md:mt-10 md:max-w-full mx-auto">
                @foreach($block->payload['order'] as $index => $item)
                    <div class="swiper-slide bg-white w-full h-auto p-4 md:p-5 rounded-20">
                        <div class="w-full flex items-center justify-center gap-8 font-bold text-2xl text-white h-11 rounded-xl {{ $index == count($block->payload['order']) - 1 ? 'btn-blue-gradient' : 'orange-gr' }}">
                            {{ $index + 1 }}
                            @if($index != count($block->payload['order']) - 1)
                                <x-icon-arrow-long-white></x-icon-arrow-long-white>
                            @endif
                        </div>
                        <h3 class="text-heading font-semibold mt-5 text-xl">{{ $item['title'] }}</h3>
                        <div class="mt-4">{!! $item['text'] !!}</div>
                    </div>
                @endforeach
            </div>
            <div class="night-lenses-selection-swiper-pagination text-center mt-4 lg:hidden [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued"></div>
            @if($block->payload['add_fox'])
                <img src="{{ asset('images/fireworks.webp') }}" alt="Веселый корги" width="215" height="288" class="absolute hidden lg:block lg:-right-20 -bottom-10 xl:-right-32 w-[160px] xl:w-[215px]">
            @endif
        </div>
    @else
        <div class="relative">
            @if($block->payload['add_fox'])
                <img src="{{ asset('images/fireworks.webp') }}" alt="Веселый корги" width="215" height="288" class="absolute hidden lg:block lg:-right-20 -bottom-10 xl:-right-32 w-[160px] xl:w-[215px]">
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ isset($block->payload['count_column']) ? $block->payload['count_column'] : 3 }} mt-5 gap-4 md:gap-6 md:mt-10 md:max-w-full mx-auto px-4">
                @foreach($block->payload['order'] as $index => $item)
                    <div class="bg-white w-full p-5 rounded-20">
                        <div class="w-full flex items-center justify-center gap-8 font-bold text-2xl text-white h-11 rounded-xl {{ $index == count($block->payload['order']) - 1 ? 'btn-blue-gradient' : 'orange-gr' }}">
                            {{ $index + 1 }}
                            @if($index != count($block->payload['order']) - 1)
                                <x-icon-arrow-long-white></x-icon-arrow-long-white>
                            @endif
                        </div>
                        <h3 class="text-heading font-semibold mt-5 text-xl">{{ $item['title'] }}</h3>
                        <div class="mt-4">{!! $item['text'] !!}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
