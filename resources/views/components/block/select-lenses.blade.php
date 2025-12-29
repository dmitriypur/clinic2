<div class="container pb-5 md:pb-10 ">
    @if (!$block->title_hidden)
        <div class="mx-auto mb-5 md:mb-10">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div class="grid md:grid-cols-2 mt-5 md:gap-x-10 gap-y-6 md:mt-10 mx-auto relative">
        @if($block->payload['add_fox'])
            <img src="{{ asset('images/fireworks.png') }}" alt="" class="absolute hidden lg:block left-1/2 top-1/2 -translate-y-1/2 -translate-x-1/2 w-[130px] xl:w-[130px]">
        @endif
        @foreach($block->payload['order'] as $index => $item)
            <div class="bg-white md:p-7 rounded-20">
                <div class="w-full flex items-center gap-3 lg:gap-6 font-bold text-xl text-white h-11 rounded-xl orange-gr px-4 lg:px-6">
                    {{ $index + 1 }}
                    <h3 class="font-semibold text-base lg:text-xl">{{ $item['title'] }}</h3>
                </div>
                <div class="text-sm md:text-base text-interactive/60 p-4 md:p-0 md:mt-3">{!! str($item['text'])->sanitizeHtml() !!}</div>
            </div>
        @endforeach
    </div>
</div>
