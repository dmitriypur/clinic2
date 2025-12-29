<div class="container">
    <div class="w-full mx-auto">
        <div class="w-full flex flex-col-reverse md:flex-row gap-x-10">
            <div class="w-auto min-w-80">
                <h2 class="hidden md:block md:text-center md:text-left font-semibold md:text-[34px]">
                    {{ $block->title }}
                </h2>
                <div class="mt-9 overflow-hidden w-full rounded-2xl border border-white">
                    <img src="{{ asset('images/guarantee/boy.jpg') }}" alt="Мальчик с очками — довольный ребёнок после лечения" class="w-full">
                </div>
            </div>
            <div class="w-full max-w-[800px]">
                <h2 class="md:hidden font-semibold text-[28px] mb-4">
                    {{ $block->title }}
                </h2>
                <div
                    class="[&_p]:md:text-2xl [&_p]:md:leading-9 [&_p]:md:mb-9">{!! $block->payload['enter_text'] ?? '' !!}</div>
                @if(count($block->payload['guarantee']))
                    <ul class="flex flex-col gap-4 md:gap-7 mt-4 md:mt-0">
                        @foreach($block->payload['guarantee'] as $item => $block)
                            <li class="md:flex md:gap-x-8 bg-surface py-6 md:py-8 px-5 rounded-2xl relative">
                                <img src="{{ asset('images/guarantee/icon-'.($item+1) .'.svg') }}" alt="{{ 'Иконка '.$block['title'] }}"
                                     class="max-w-11 md:max-w-20 absolute top-4 md:relative md:top-0">
                                <div>
                                    <p class="text-lg font-semibold mb-4 md:mb-2 pl-16 md:pl-0">{{ $block['title'] }}</p>
                                    <p class="text-sm md:text-interactive/55">{{ $block['text'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
