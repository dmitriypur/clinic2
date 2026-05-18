@php
    $items = collect($block->diagnostic_methods_items ?? []);
@endphp

@if($items->isNotEmpty())
    <div class="container">
        <div class="treatment-methods-block rounded-3xl bg-white px-4 py-5 md:px-8 md:py-8 lg:px-10 lg:py-10">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,680px)] lg:gap-14">
                <div class="flex flex-col gap-4 md:gap-6">
                    @if(!$block->title_hidden)
                        <h2 class="text-2xl font-semibold leading-tight text-heading md:text-3xl">
                            {{ $block->title }}
                        </h2>
                    @endif

                    @if($block->body_html)
                        <div class="space-y-4 leading-6 text-heading [&_p]:m-0">
                            {!! str($block->body_html)->sanitizeHtml() !!}
                        </div>
                    @endif
                </div>

                    <div class="space-y-3 md:space-y-4">
                        @foreach($items as $item)
                            <x-block.partials.diagnostic-method-card
                                :item="$item"
                                compact
                            />
                        @endforeach
                    </div>
                </div>
        </div>
    </div>
@endif
