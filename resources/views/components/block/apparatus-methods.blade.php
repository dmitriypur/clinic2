@php
    $items = collect($block->apparatus_methods_items ?? []);
    $buttonText = $block->payload['btn_text'] ?? 'Подробнее';
@endphp

@if($items->isNotEmpty())
    <div class="container">
        <div class="py-4">
            @if(!$block->title_hidden)
                <div class="mx-auto max-w-5xl">
                    <h2 class="text-2xl md:text-4xl font-semibold leading-tight text-heading text-center">
                        {{ $block->title }}
                    </h2>
                </div>
            @endif

            @if($block->body_html)
                <div class="mx-auto mt-4 max-w-2xl text-center text-base leading-6 text-heading md:mt-6 md:leading-7 [&_p:not(:last-child)]:mb-4">
                    {!! str($block->body_html)->sanitizeHtml() !!}
                </div>
            @endif

            <div class="mt-6 space-y-4 md:mt-8 md:space-y-3">
                <faq inline-template>
                    <div>
                        @foreach($items as $item)
                            <item inline-template>
                                <article class="rounded-20 bg-white p-5 md:p-4 mb-8">
                                    <div class="hidden md:flex md:items-center md:gap-5">
                                        @if(!empty($item['image_html']))
                                            <div class="w-52 shrink-0 overflow-hidden rounded-xl self-stretch min-h-full">
                                                <div class="h-40 [&_img]:h-full [&_img]:w-full [&_img]:object-cover h-full">
                                                    {!! $item['image_html'] !!}
                                                </div>
                                            </div>
                                        @endif

                                        <div class="min-w-0 flex-1">
                                            <h3 class="text-lg font-bold leading-tight text-heading">
                                                {{ $item['title'] ?? '' }}
                                            </h3>

                                            @if(!empty($item['body_html']))
                                                <div class="mt-2 text-base leading-6 text-heading [&_p:not(:last-child)]:mb-3 [&_strong]:font-bold">
                                                    {!! str($item['body_html'])->sanitizeHtml() !!}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="hidden w-48 shrink-0">
                                            <x-button-primary class="w-full text-base">
                                                {{ $buttonText }}
                                            </x-button-primary>
                                        </div>
                                    </div>

                                    <div class="md:hidden">
                                        @if(!empty($item['image_html']))
                                            <div class="overflow-hidden rounded-xl">
                                                <div class="h-40 [&_img]:h-full [&_img]:w-full [&_img]:object-cover">
                                                    {!! $item['image_html'] !!}
                                                </div>
                                            </div>
                                        @endif

                                        <div class="mt-4">
                                            <button
                                                type="button"
                                                class="flex w-full items-center gap-3 text-left"
                                                @click="toggle"
                                            >
                                                <span class="flex-1 text-lg font-bold leading-tight text-heading">
                                                    {{ $item['title'] ?? '' }}
                                                </span>
                                                <span class="flex h-6 w-6 items-center justify-center text-heading transition-transform duration-200" :class="open ? '-rotate-90' : 'rotate-90'">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                        <path d="M10 6L16 12L10 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </span>
                                            </button>
                                        </div>

                                        <div v-show="open" class="mt-4">
                                            @if(!empty($item['body_html']))
                                                <div class="text-base leading-6 text-heading [&_p:not(:last-child)]:mb-3 [&_strong]:font-bold">
                                                    {!! str($item['body_html'])->sanitizeHtml() !!}
                                                </div>
                                            @endif

                                            <button
                                                type="button"
                                                class="mt-3 block w-full text-right text-base text-action-primary"
                                                @click="toggle"
                                            >
                                                Свернуть
                                            </button>
                                        </div>

                                        <button
                                            type="button"
                                            class="mt-4 flex w-full items-center justify-center rounded-xl border border-black px-6 py-4 text-base font-semibold leading-tight text-black"
                                        >
                                            {{ $buttonText }}
                                        </button>
                                    </div>
                                </article>
                            </item>
                        @endforeach
                    </div>
                </faq>
            </div>
        </div>
    </div>
@endif
