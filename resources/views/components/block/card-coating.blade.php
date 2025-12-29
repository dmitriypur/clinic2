<div class="overflow-hidden pb-24">
    <div class="container relative z-10">
        <img src="{{ asset('images/linza.png') }}" alt="Линза" class="hidden md:block md:w-[180px] md:h-auto md:absolute md:scale-x-[-1] md:left-[-100px] md:bottom-[-110px] md:z-[-1]">
        <img src="{{ asset('images/linza.png') }}" alt="Линза" class="w-[50px] md:w-[90px] h-auto absolute left-[75%] top-[100px] md:left-[97%] md:top-[10px] scale-x-[-1] md:scale-x-[1]">
        @if(!$block->title_hidden)
            <div class="mx-auto px-10 mb-6 md:mb-12">
                <h2 class="font-semibold text-2xl md:text-3xl text-center text-heading">
                    {{ $block->title }}
                </h2>
            </div>
        @endif

        <ul class="flex flex-wrap w-full gap-10 md:flex-nowrap">
            @foreach($block->payload['coating'] as $item)
                <li class="flex flex-col w-full md:w-1/3 bg-white rounded-3xl p-5">
                    <span class="inline-block text-sm bg-[#F4F6F9] py-2 px-4 rounded-lg self-start">Линза Stellest®</span>
                    <h4 class="text-xl mt-2 mb-4">{!! $item['title'] !!}</h4>
                    <div class="coating__card-list text-xs leading-5">
                        {!! $item['list'] !!}
                    </div>
                    @if($item['colors'])
                        <div class="md:mb-4 mt-2 flex justify-between items-center bg-surface-subdued py-1.5 px-2.5 rounded-lg">
                            <p class="text-sm">Цвет линзы на выбор:</p>
                            <ul class="flex gap-1">
                                <li style="background: #663485" class="w-6 h-6 rounded-full"></li>
                                <li style="background: #5F2644" class="w-6 h-6 rounded-full"></li>
                                <li style="background: #094E77" class="w-6 h-6 rounded-full"></li>
                                <li style="background: #213349" class="w-6 h-6 rounded-full"></li>
                                <li style="background: #6E5944" class="w-6 h-6 rounded-full"></li>
                                <li style="background: #09596A" class="w-6 h-6 rounded-full"></li>
                            </ul>
                        </div>
                    @endif

                    <div class="mt-6 md:mt-auto">
                        <x-button-blue
                            @click="showCallbackModal(null, 'otpravka-formy')"
                            class="w-full font-bold rounded-lg">
                            <span>{{ $item['btn_text'] }}</span>
                        </x-button-blue>
                        <x-button-primary
                            @click="showCallbackModal(null, 'otpravka-formy')"
                            class="w-full font-bold rounded-lg mt-[15px]">
                            Купить по специальной цене
                        </x-button-primary>
                    </div>
                </li>

            @endforeach
        </ul>
    </div>
</div>
