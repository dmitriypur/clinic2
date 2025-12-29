<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto mb-6 md:mb-12">
            <h2 class="font-medium text-2xl md:text-3xl text-left text-heading [&_span]:text-action-primary md:[&_span]:text-interactive">
                {!! $block->title !!}
            </h2>
        </div>
    @endif

    <ul class="flex flex-col flex-wrap gap-6 md:gap-0 md:flex-row w-full xl:flex-nowrap">
        @foreach($block->payload['order'] as $index => $item)
            <li class="order-list">
                <p class="text-lg tracking-tight"><span class="mr-2 text-xl text-[#C3C5CE]">0{{$index + 1}}</span>{{ $item['title'] }}</p>
                <div
                    class="orange-line absolute top-0 left-0 md:relative rounded-full bg-action-primary-{{100 * ($index + 1)}} md:my-[14px] h-[calc(100%+50px)] w-6 md:h-6 xl:w-[calc(100%+40px)] md:w-[calc(100%+20px)]"></div>
                <div class="md:pr-4 text-sm leading-5 opacity-70">{!! str($item['text'])->sanitizeHtml() !!}</div>
            </li>
        @endforeach
    </ul>
        <div class="flex justify-center">
            <x-button-primary
                @click="showCallbackModal(null, 'otpravka-formy')"
                class="w-[412px] font-bold rounded-lg mt-5 md:mt-10 md:px-0 md:text-xl">
                Записаться на приём
            </x-button-primary>
            <span class="bg-action-primary-100"></span>
            <span class="bg-action-primary-200"></span>
            <span class="bg-action-primary-300"></span>
            <span class="bg-action-primary-400"></span>
        </div>

</div>
