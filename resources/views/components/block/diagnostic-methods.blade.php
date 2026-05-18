@php
    $items = collect($block->diagnostic_methods_items ?? []);
    $image = $block->getResponsiveImage('default', $block->title);
@endphp

@if($items->isNotEmpty())
    <div class="container">
        <div class="diagnostic-methods-block">
            <div class="grid gap-6 {{ $image ? 'lg:lg:grid-cols-[minmax(0,1fr)_minmax(0,720px)] lg:gap-10 xl:gap-12' : '' }}">
                @if($image)
                    <div class="hidden overflow-hidden rounded-3xl lg:block [&_img]:h-full [&_img]:w-full [&_img]:object-cover">
                        {{ $image }}
                    </div>
                @endif

                <div class="flex flex-col gap-5 md:gap-6">
                    @if(!$block->title_hidden)
                        <h2 class="text-center text-2xl font-semibold leading-tight text-heading md:text-3xl lg:text-left">
                            {{ $block->title }}
                        </h2>
                    @endif

                    @if($block->body_html)
                        <div class="space-y-4 leading-6 text-heading [&_p]:m-0">
                            {!! str($block->body_html)->sanitizeHtml() !!}
                        </div>
                    @endif

                    @if(!empty($block->payload['cards_intro']))
                        <p class="font-semibold leading-6 text-heading">
                            {{ $block->payload['cards_intro'] }}
                        </p>
                    @endif

                    <div class="space-y-3 md:space-y-4">
                        @foreach($items as $item)
                            <x-block.partials.diagnostic-method-card :item="$item"/>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
