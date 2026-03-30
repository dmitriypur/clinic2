@php
    $items = collect($block->payload['items'] ?? []);
@endphp

@if($items->isNotEmpty())
    <div class="container">
        <div class="rounded-3xl bg-white p-4 md:px-10 md:py-10">
            <div class="md:grid md:grid-cols-12 md:gap-8">
                <div class="md:col-span-5">
                    @if(!$block->title_hidden)
                        <h2 class="text-lg md:text-4xl font-bold md:font-semibold leading-tight text-heading">
                            {{ $block->title }}
                        </h2>
                    @endif

                    @if($block->body_html)
                        <div class="mt-6 text-base leading-6 text-heading md:leading-7 [&_p:not(:last-child)]:mb-4">
                            {!! str($block->body_html)->sanitizeHtml() !!}
                        </div>
                    @endif
                </div>

                <div class="mt-6 md:mt-0 md:col-span-7">
                    <div class="grid gap-4 md:grid-cols-2 md:gap-5">
                        @foreach($items as $item)
                            <div class="flex items-center gap-3 rounded-2xl bg-action-primary/10 px-4 py-3 md:px-4 md:py-4">
                                <div class="h-10 w-10 shrink-0 text-action-primary">
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-full w-full" aria-hidden="true">
                                        <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2Zm1 15h-2v-2h2v2Zm0-4h-2V7h2v6Z"/>
                                    </svg>
                                </div>

                                <div class="text-base leading-6 text-heading md:leading-5">
                                    {!! nl2br(e($item['text'] ?? '')) !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
