@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

@isset($block->payload['text_content'])
    <read-more inline-template :is-opening="{{ !empty($block->payload['is_opening']) ? 1 : 0 }}">
        <div class="pb-0 container">
            <div class="relative flex flex-col gap-4 md:gap-10 container p-5 md:p-11 bg-white overflow-hidden {{ !empty($block->payload['is_rounded']) ? 'rounded-20' : ''}}"
                 ref="infoBox">

                @foreach($block->payload['text_content'] as $item)
                    <div class="relative">
                        <div class="
                            {{ $item['picture'] ? 'md:min-h-[335px] md:max-w-[90%] md:pr-[300px]' : '' }}
                            {{ implode(' ', $item['classes']) }}"
                        >
                            @if(!empty($item['title']))
                                <h3 class="text-xl md:text-3xl md:leading-9 font-semibold [&_span]:text-action-primary mb-5">{!! str($item['title'])->sanitizeHtml() !!}</h3>
                            @endif
                            @if(!empty($item['subtitle']))
                                <h4 class="text-lg md:text-2xl md:leading-9{{ !empty($item['body_html']) ? 'mb-5' : '' }}">{!! str($item['subtitle'])->sanitizeHtml() !!}</h4>
                            @endif
                            <div
                                class="content-block md:text-xl">
                                {!! str($item['body_html'])->sanitizeHtml() !!}
                            </div>
                            @if(!empty($item['grid_blocks']))
                                <div class="md:grid md:grid-cols-{{ $item['cols_count'] }} md:gap-2.5 md:-ml-6 md:-mr-6">
                                    @foreach($item['grid_blocks'] as $element)
                                        <div
                                            class="mb-3 md:mb-0 col-span-2 lg:col-span-{{ $element['col_count'] }} bg-interactive text-white/50 font-medium text-center p-4 [&_strong]:text-white rounded-lg min-h-24 flex items-center justify-center">
                                            {!! str($element['body_html'])->sanitizeHtml() !!}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @if($item['picture'])
                            <div class="
                                md:mt-0 md:absolute md:z-10 md:top-0 md:right-0 -mx-5 p-5 md:mx-0 md:p-0 md:bg-transparent
                                {{ str_contains(implode(' ', $item['classes']), 'gray') !== false ? 'bg-surface-subdued' : ''}}
                                ">
                                <img src="{{ url('storage') . '/' . $item['picture'] }}"
                                     class="w-full md:max-w-96 rounded-lg md:rounded-2xl" width="380"
                                     height="335" alt="Фото">
                            </div>
                        @endif
                    </div>
                @endforeach

                @if($block->payload['is_opening'])
                    <span v-if="!open"
                          class="absolute left-0 bottom-0 w-full h-64 z-10 bg-gradient-to-t from-white to-transparent"></span>
                @endif
            </div>
            @if($block->payload['is_opening'])
                <div class="flex items-center justify-center bg-white rounded-lg w-10 h-10 mx-auto mt-6">
                    <x-arrow-down width="16" height="26"></x-arrow-down>
                </div>
                <div class="px-2">
                    <x-button-primary @click.self="toggle"
                                      class="mx-auto block w-full max-w-[450px] md:h-16 md:text-2xl mt-5">@{{ !open ?
                        'Смотреть статью полностью' : 'Свернуть статью' }}
                    </x-button-primary>
                </div>
            @endif
        </div>
    </read-more>

@endisset
