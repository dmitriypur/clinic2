@php
    $items = collect($block->payload['items'] ?? []);
    $image = $block->getResponsiveImage('default', $block->title, 'apparatus');
@endphp

@if($items->isNotEmpty())
    <div class="container">
        <div class="py-4 md:rounded-3xl md:bg-white md:px-10 md:py-10">
            <div class="md:flex md:items-start md:gap-8">
                <div class="md:w-5/12 md:flex md:flex-col">
                    @if(!$block->title_hidden)
                        <h2 class="text-2xl md:text-4xl font-semibold leading-tight text-heading text-center md:text-left">
                            {{ $block->title }}
                        </h2>
                    @endif

                    @if($block->body_html)
                        <div class="mt-6 text-base leading-6 text-heading [&_p:not(:last-child)]:mb-4">
                            {!! str($block->body_html)->sanitizeHtml() !!}
                        </div>
                    @endif

                    @if($image)
                        <div class="hidden md:block md:mt-10">
                            <div class="relative rounded-3xl bg-gradient-to-b from-white to-action-primary-light md:h-44 md:mt-10">
                                <div class="absolute inset-x-0 bottom-0 flex justify-center">
                                    <div class="w-72 md:w-80 [&_img]:h-auto [&_img]:w-full [&_img]:object-contain [&_img]:object-bottom">
                                        {!! $image !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 md:mt-0 md:w-7/12">
                    <div class="grid grid-cols-2 gap-4 md:gap-5">
                        @foreach($items as $index => $item)
                            <div class="relative pt-3">
                                <div class="absolute left-1/2 top-0 -translate-x-1/2 inline-flex min-w-8 h-6 items-center justify-center rounded-lg bg-action-primary px-3 text-xs font-semibold leading-none text-white md:min-w-12 md:px-5">
                                    {{ $index + 1 }}
                                </div>

                                <div class="flex h-24 items-center justify-center rounded-20 border border-heading/20 bg-white px-3 py-4 text-center text-sm font-bold leading-6 text-heading md:px-4 md:text-base md:font-semibold md:leading-tight">
                                    {!! nl2br(e($item['text'] ?? '')) !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if($image)
                <div class="mt-10 md:hidden">
                    <div class="relative h-44 rounded-3xl bg-gradient-to-b from-white to-action-primary-light">
                        <div class="absolute inset-x-0 bottom-0 flex justify-center">
                            <div class="w-72 [&_img]:h-auto [&_img]:w-full [&_img]:object-contain [&_img]:object-bottom">
                                {!! $image !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
