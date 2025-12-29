<div class="container">
    <div class="">
        @if(!$block->title_hidden)
            <div class="mx-auto md:rounded-2xl bg-action-primary-light p-4 md:p-6">
                <h2 class="font-semibold text-2xl md:text-3xl text-center text-heading">
                    {{ $block->title }}
                </h2>
            </div>
        @endif

        <div class="">
            @if(!empty($block->payload['grid']))
                <div class="grid gap-5">
                    @foreach($block->payload['grid'] as $item)
                        <div class="flex flex-col md:flex-row items-center bg-surface rounded-xl md:rounded-20 p-5 md:px-4 md:py-2.5">
                            <img src="{{ asset('images/open-folder.svg') }}" width="35" height="35"
                                 alt="Иконка открытой папки с документами">
                            <div class="text-2xl text-center md:text-left font-semibold mt-4 md:mt-0 md:ml-5">
                                {!! $item['text'] !!}
                            </div>

                            @if(!empty($item['document']))
                                <a href="{{ '/storage/' . $item['document'] }}" target="_blank"
                                   class="py-3 px-6 btn-gradient font-semibold text-white rounded-xl w-full max-w-[230px] mt-8 md:mt-0 md:ml-auto text-center">Открыть
                                    документ</a>
                            @endif

                            @if(!empty($item['link']))
                                <a href="{{ city_route('pages.show', ['handle' => $item['link']]) }}" class="py-3 px-6 btn-gradient font-semibold text-white rounded-xl w-full max-w-[230px] mt-8 md:mt-0 md:ml-auto text-center">Подробнее</a>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-10 flex flex-col md:flex-row items-center bg-action-primary-light md:bg-surface rounded-xl md:rounded-20 p-5">
                    <img src="{{ asset('images/person.svg') }}" width="55" height="55"
                         alt="Иконка открытой папки с документами">
                    <div class="text-2xl md:text-3xl text-center md:text-left font-semibold mt-4 md:mt-0 md:ml-12">
                        Медицинские сотрудники
                    </div>

                    <a href="{{ city_route('pages.show', ['handle' => 'doctors']) }}"
                       class="py-3 px-6 btn-gradient font-semibold text-white rounded-xl md:px-10 mt-8 md:mt-0 md:ml-auto text-center">Перейти на страницу врачей</a>
                </div>
            @endif
        </div>
    </div>
</div>

