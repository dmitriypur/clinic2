<div class="container px-0 pb-5 md:pb-10 ">
    @if (!$block->title_hidden)
        <div class="max-w-2xl mx-auto mb-5 md:mb-10">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div class="flex flex-wrap mt-5 md:gap-5 md:mt-10 md:max-w-[86%] mx-auto relative">
        @if($block->payload['add_fox'])
            <img src="{{ asset('images/fireworks.webp') }}" alt="Веселый корги" width="215" height="288" class="absolute hidden lg:block lg:-right-20 -bottom-10 xl:-right-32 w-[160px] xl:w-[215px]">
        @endif
        @foreach($block->payload['order'] as $index => $item)
            <div class="bg-white md:w-[calc(50%-0.7rem)] lg:w-[calc(33.33333%-0.9rem)] md:p-5 rounded-20">
                <div class=" w-full flex items-center justify-center gap-8 font-bold text-2xl text-white h-11 rounded-xl {{ $index == count($block->payload['order']) - 1 ? 'btn-blue-gradient' : 'orange-gr' }}">
                    {{ $index + 1 }}
                    @if($index != count($block->payload['order']) - 1)
                        <x-icon-arrow-long-white></x-icon-arrow-long-white>
                    @endif
                </div>

                <h3 class="text-heading font-medium mt-5 text-sm md:text-2xl">{{ $item['title'] }}</h3>
                <div class="{{ $index == count($block->payload['order']) - 1 ? 'text-xl mt-6' : 'text-lg leading[120%] text-interactive/55 mt-4' }}">{{ $item['text'] }}</div>
            </div>
        @endforeach
    </div>
</div>
