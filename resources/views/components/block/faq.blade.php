@push('scripts')
    {!! Clinic::schema()->faq($block->payload['faq']) !!}
@endpush


<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto px-10 mb-5 md:mb-10">
            <h2 class="font-semibold text-2xl md:text-3xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif
    <faq inline-template>
        <div>

            <dl class="space-y-5 text-interactive">
                @foreach($block->payload['faq'] as $id => $item)
                    <item inline-template :count="countItems">
                        <details v-show="{{$id + 1}} <= count" class="bg-[#F9F9F9] rounded-2xl">
                            <summary
                                class="flex items-center gap-4 cursor-pointer border border-[#E5E7EB] p-4 bg-surface rounded-2xl"
                                @click.self="toggle({{$id}})">
                                <div class="aspect-square h-10 w-10 md:h-16 md:w-16 bg-cover pointer-events-none"
                                     style="background-image: @if($item['icon']) url('storage/{{ $item['icon'] }}' @else url({{ asset('images/faq.png') }} @endif"></div>
                                <div
                                    class="leading-none md:text-xl font-medium select-none pointer-events-none"
                                    @click.self="toggle">{{ $item['question'] }}</div>
                                <button :class="className">+</button>
                            </summary>
                            <div v-show="open"
                                 class="px-4 py-6 md:px-20 md:py-8 content md:text-xl text-interactive/80 select-none [&_h3]:text-white [&_h3]:bg-interactive [&_h3]:rounded-lg [&_h3]:p-2">{!! $item['answer_html'] !!}</div>
                        </details>
                    </item>
                @endforeach
            </dl>

            <x-button-primary v-if="{{ count($block->payload['faq']) }} > 4" @click.self="toggleAll({{count($block->payload['faq'])}})"
                              class="mx-auto block w-full max-w-[450px] md:h-16 md:text-xl mt-5 md:mt-10">@{{ !openAll
                ?
                'Посмотреть остальные вопросы' : 'Скрыть остальные вопросы' }}
            </x-button-primary>
        </div>
    </faq>
</div>
