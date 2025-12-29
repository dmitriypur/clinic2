<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto px-10 mb-6 md:mb-12">
            <h2 class="font-semibold text-2xl md:text-3xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div class="flex flex-wrap md:flex-nowrap gap-6 lg:h-[418px] relative">
        <div class="w-full md:max-w-[455px] flex flex-col gap-8">
            <div class="bg-action-primary p-6 rounded-3xl w-full text-white lg:h-[50%]">
                <h3 class="text-2xl font-bold mb-2">{{ $block->payload['var_1_title'] }}</h3>
                <p>{{ $block->payload['var_1_text'] }}</p>
            </div>
            <div class="bg-white p-6 rounded-3xl w-full text-interactive lg:h-[50%]">
                <h3 class="text-2xl font-bold mb-2">{{ $block->payload['var_2_title'] }}</h3>
                <p>{{ $block->payload['var_2_text'] }}</p>
            </div>
        </div>
        <div class="rounded-3xl bg-white w-full">
            <img class="w-full h-auto" src="{{$block->getFirstMediaUrl('bg_chart')}}"
                 alt="{{ $block->title }}">
        </div>
        <img src="{{ asset('images/fireworks.png') }}" alt="Милая корги в очках" class="absolute bottom-[-80px] md:bottom-[-150px] right-0 md:right-3 w-[140px] md:w-[215px]">
    </div>

</div>
