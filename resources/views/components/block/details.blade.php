@php($details = $currentCity->details_items)
@php($detail = $details[0] ?? ['name' => null, 'rows' => []])

<div class="container">
    <div class="flex flex-col md:flex-row md:gap-x-8">
        <div class="w-full md:w-1/2 flex flex-col">
            @if(!$block->title_hidden)
                <h1 class="font-semibold text-center text-2xl md:text-4xl text-heading [&_span]:font-normal [&_span]:block md:[&_span]:inline m-auto max-w-lg mb-5 md:mb-10 mt-0">
                    {!! $block->title !!}
                </h1>
            @endif
            <div class="bg-white rounded-2xl md:rounded-3xl p-6 md:p-10 flex-auto">
                <article>
                    @if($detail['name'])
                        <h3 class="text-2xl font-semibold leading-7">
                            {{ $detail['name'] }}
                        </h3>
                    @endif

                    @if(count($detail['rows']))
                        <div @class([
                            'mt-6 text-base grid gap-x-16 gap-y-4 space-y-4'
                        ])>
                            @foreach([$detail['rows']] as $column)
                                <div class="space-y-4">
                                    @foreach($column as $row)
                                        <div @class([
                                            'border-t border-dashed border-interactive/30 pt-4' => !$loop->first,
                                        ])>
                                            <div class="font-semibold leading-5">
                                                {{ $row['label'] }}
                                            </div>
                                            <div class="mt-1 leading-5">
                                                {{ $row['value'] }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            </div>
        </div>

        <div class="w-full md:w-1/2 flex flex-col mt-7 md:m-0">
            <h2 class="font-semibold text-center text-2xl md:text-4xl text-heading [&_span]:font-normal [&_span]:block md:[&_span]:inline m-auto max-w-xs mb-5 md:mb-10 mt-0">Медицинская деятельность</h2>
            <div class="flex-auto w-full bg-white rounded-2xl md:rounded-3xl p-6 md:p-10 text-heading">
                @if(filled($block->payload['activity_html'] ?? null))
                    {!! $block->payload['activity_html'] !!}
                @endif
                <div class="mt-6 flex min-h-[168px] flex-col items-end justify-center gap-4 rounded-[10px] bg-[#EBF0F3] p-4">
                    <div class="flex w-full items-center gap-4">
                        <img
                            src="{{ asset('images/document-license.svg') }}"
                            alt=""
                            width="40"
                            height="40"
                            class="size-10 shrink-0"
                        >
                        <div class="min-w-0 text-base leading-[1.3]">
                            <div class="font-bold">Лицензия на осуществление медицинской деятельности</div>
                            
                            @if(filled($block->payload['number_license'] ?? null))
                            <div>
                                {!! $block->payload['number_license'] !!}
                            </div>
                            @endif
                            
                        </div>
                    </div>

                    <x-document-link
                        :href="$activityMediaUrl ?? asset('licenzii-i-iuridiceskaia-informaciia')"
                        mobile="button"
                        desktop-at="md"
                    />
                </div>

                <div class="mt-4 flex items-center gap-4 rounded-[10px] bg-[#FBEDDF] px-4 py-3 text-xs font-semibold leading-[1.2] md:mt-6 md:py-6 md:text-sm md:font-normal md:leading-[1.4]">
                    <img
                        src="{{ asset('images/attention.svg') }}"
                        alt=""
                        width="24"
                        height="24"
                        class="size-6 shrink-0"
                    >
                    <p>
                        @if($detail['name']) {{ $detail['name'] }} @endif (Центр детского зрения «Ангелы зрения») НЕ ОКАЗЫВАЕТ УСЛУГ в рамках программы государственных гарантий бесплатного оказания гражданам медицинской помощи и территориальной программы государственных гарантий бесплатного оказания гражданам медицинской помощи.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
