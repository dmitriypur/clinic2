@php
    $documents = $block->payload['grid'] ?? [];
@endphp

<div class="container">
    <div @class([
        '-mx-4 rounded-3xl bg-surface px-6 py-6 md:mx-0 lg:p-10',
        'lg:grid lg:grid-cols-2 lg:gap-10' => !empty($documents),
    ])>
        <div class="text-heading">
            @if(!$block->title_hidden)
                <h2 class="text-xl font-semibold leading-[1.2] lg:text-[34px]">
                    {{ $block->title }}
                </h2>
            @endif

            @if(!empty($block->payload['html']))
                <div @class([
                    'text-base leading-[1.3] [&_li]:list-disc [&_li]:text-base [&_li]:leading-[1.3] [&_li]:marker:text-heading [&_ul]:m-0 [&_ul]:space-y-4 [&_ul]:pl-6',
                    'mt-4' => !$block->title_hidden,
                ])>
                    {!! $block->payload['html'] !!}
                </div>
            @endif
        </div>

        @if(!empty($documents))
            <div class="mt-4 flex flex-col gap-3 lg:mt-0">
                @foreach($documents as $item)
                    <div class="relative flex min-h-[72px] items-center rounded-[10px] bg-surface-subdued p-4 text-heading lg:min-h-[80px] lg:gap-4">
                        <span class="hidden size-10 shrink-0 items-center justify-center lg:flex">
                            <img src="{{ asset('images/document-license.svg') }}"
                                 width="28"
                                 height="36"
                                 alt=""
                                 class="h-9 w-7">
                        </span>

                        <div @class([
                            'min-w-0 flex-1 font-bold leading-[1.3]',
                            'underline decoration-1 underline-offset-2 lg:no-underline' => !empty($item['document']),
                        ])>
                            {!! $item['text'] !!}
                        </div>

                        @if(!empty($item['document']))
                            <a href="{{ '/storage/' . $item['document'] }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               aria-label="Открыть документ: {{ strip_tags($item['text']) }}"
                               class="absolute inset-0 rounded-[10px] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-heading lg:static lg:inline-flex lg:shrink-0 lg:items-center lg:justify-center lg:whitespace-nowrap lg:border lg:border-heading lg:px-[30px] lg:py-4 lg:font-semibold lg:leading-[1.22] lg:transition-colors lg:hover:bg-heading lg:hover:text-white">
                                <span class="hidden lg:inline">Открыть документ</span>
                            </a>
                        @else
                            <span class="hidden shrink-0 items-center justify-center whitespace-nowrap rounded-[10px] border border-heading px-[30px] py-4 font-semibold leading-[1.22] lg:inline-flex">
                                Открыть документ
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
