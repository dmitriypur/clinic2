<div class="container">
    <div class="-ml-4 -mr-4 md:m-0 bg-surface md:rounded-3xl">
        @if(!$block->title_hidden)
            <div class="mx-auto md:rounded-2xl bg-action-primary-light p-4 md:p-6">
                <h2 class="font-semibold text-2xl md:text-3xl text-center text-heading">
                    {{ $block->title }}
                </h2>
            </div>
        @endif

        <div class="p-5 md:px-5 md:py-0">
            {!! $block->payload['html']  !!}
            @if(!empty($block->payload['grid']))
                @if(count($block->payload['grid']) > 3)
                    <div class="grid md:grid-cols-3 gap-10 md:gap-20 py-6 md:py-10 md:px-12">
                        @foreach($block->payload['grid'] as $item)
                            <div class="space-y-5 flex flex-col">
                                <img src="{{ asset('images/open-folder.svg') }}" alt="" width="44" height="44">
                                <div class="grow font-semibold text-xl/6">{{ $item['text'] }}</div>
                                @if(!empty($item['document']))
                                    <a href="{{ '/storage/' . $item['document'] }}" target="_blank"
                                       class="py-3 px-6 btn-gradient font-semibold text-white rounded-xl px-14 text-center">Открыть
                                        документ</a>
                                @else
                                    <x-button-primary>Открыть
                                        документ</x-button-primary>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="grid gap-10">
                        @foreach($block->payload['grid'] as $item)
                            <div class="flex flex-col md:flex-row md:items-center justify-center pt-10 pb-2 md:py-12">
                                <img src="/images/open-folder.svg" width="54" height="54"
                                     alt="Иконка открытой папки с документами">
                                <div class="text-xl mt-4 md:mt-0 md:ml-5">
                                    {!! $item['text'] !!}
                                </div>

                                @if(!empty($item['document']))
                                    <a href="{{ '/storage/' . $item['document'] }}" target="_blank"
                                       class="py-3 px-6 btn-gradient font-semibold text-white rounded-xl px-14 mt-8 md:mt-0 md:ml-16 text-center">Открыть
                                        документ</a>
                                @else
                                    <x-button-primary class="mt-8 md:mt-0 md:ml-16">Открыть
                                        документ</x-button-primary>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

            @endif
        </div>
    </div>
</div>

