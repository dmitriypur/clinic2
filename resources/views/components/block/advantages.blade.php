<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto mb-6 md:mb-12">
            <h2 class="font-semibold text-2xl md:text-3xl text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div class="flex flex-col gap-y-5 lg:flex-row lg:gap-x-8 items-center">
        <div class="flex-auto">
            <ul class="flex flex-col gap-5">
                @foreach($block->payload['advantages'] as $item)
                    <li class="flex items-center w-full bg-white rounded-2xl py-5 px-7">
                        <div class="min-w-10 w-10 h-10 md:min-w-12  md:w-12 md:h-12">
                            <img src="{{ url('storage') . '/' . $item['icon'] }}" class="w-full h-full object-contain" width="40" height="40" alt="{{ $item['alt_image'] ?? '' }}">
                        </div>
                        <p class="ml-4 md:ml-6 font-medium leading-[130%]">{{ $item['text'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="md:mt-0 lg:flex-initial lg:shrink-0 max-w-[468px] w-full ">
            <img class="w-full md:object-contain md:max-h-[350px]" src="{{$block->getFirstMediaUrl('bg_chart')}}"
                 alt="{{ $block->payload['image_title'] ?? 'Милая корги в очках' }}" width="380" height="370">
        </div>
    </div>
</div>
