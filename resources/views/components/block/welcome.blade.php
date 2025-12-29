@if(!$block->title_hidden)
    <div class="container">
        <div class="bg-surface-subdued rounded-2xl md:bg-transparent mx-auto px-3 py-6 md:px-10 mb-5 md:mb-12">
            <h2 class="font-semibold text-2xl/7 md:text-4xl md:text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    </div>
@endif

<div class="container">

    <div class="md:grid md:grid-cols-2 gap-11">
        <div>
            <div class="relative hidden">
                <ul class="flex flex-col items-end gap-3">
                    <li class="hidden md:max-w-[70%] md:block md:w-full md:py-4 md:px-[70px] md:border md:border-action-primary md:rounded-2xl md:text-40/10">
                    <span class="max-w-[72%] float-right block [&_span]:font-bold [&_span]:text-action-primary">
                        {!! $block->elementToSpanWrap($block->payload['image_title']) !!}
                    </span>
                    </li>
                    <li class="w-full py-20 px-3 md:py-9 md:px-[70px] bg-action-primary-light rounded-2xl md:text-[22px]">
                    <span class="max-w-44 md:max-w-[50%] block md:float-right">
                        {{ $block->payload['image_subtitle'] ?? '' }}
                    </span>
                    </li>
                    <li class="w-full h-24 light-blue-gradient rounded-2xl accessibility:opacity-0"></li>
                </ul>
                <div class="accessibility:hidden absolute bottom-0 left-7 z-10 md:left-full md:-translate-x-28 w-[120px] h-[154px] md:w-[140px] md:h-[180px] [&_img]:w-full">
                    <img src="{{ asset('images/corgi-cap-glasses.webp') }}" alt="Улыбающаяся корги в кепке и очках"
                         width="140" height="180">
                </div>
                <div
                    class="absolute w-56 md:w-72 bottom-0 left-full -translate-x-full md:translate-x-0 md:left-4 [&_img]:w-full"
                    itemscope
                    itemtype="http://schema.org/ImageObject">
                    <span itemprop="name" class="hidden">{{ $block->title  }}</span>
                    {{ $block->getFirstMedia()->img('main')->attributes(['alt' => $block->title, 'itemprop' => 'contentUrl']) }}
                    <span itemprop="description"
                          class="hidden">{{ $block->title }}</span>
                </div>
            </div>
            <div class="relative">
                <div
                    class="w-full h-full md:h-[400px] [&_img]:h-full [&_img]:w-full [&_img]:object-contain"
                    itemscope
                    itemtype="http://schema.org/ImageObject">
                    <span itemprop="name" class="hidden">{{ $block->title  }}</span>
                    {{ $block->getFirstMedia()->img('main-post-750')->attributes(['alt' => $block->title, 'itemprop' => 'contentUrl']) }}
                    <span itemprop="description"
                          class="hidden">{{ $block->title }}</span>
                </div>
            </div>
        </div>
        <div>
            <div is="text-accordion" inline-template
                 :count="{{ count($block->paragraphs) }}">
                <div>
                    <div
                        class="content [&_strong]:block [&_strong]:text-center lg:[&_strong]:text-left [&_strong]:text-interactive [&_strong]:text-base  [&_a]:text-interactive [&_a]:underline [&_a:hover]:no-underline [&_a:visited]:text-purple-800 mt-6 lg:mt-0">
                        <div class="space-y-4 [&_strong]:text-lg md:text-lg">
                            {!! $block->bodyHtmlParts[0] !!}
                        </div>
                        @if ($block->bodyHtmlParts[1])
                            <div
                                :class="className">
                                <div class="space-y-2 [&_strong]:text-lg md:text-lg">
                                    {!! $block->bodyHtmlParts[1] !!}
                                </div>
                            </div>
                        @endif
                    </div>
                    @if ($block->bodyHtmlParts[1])
                        <x-button-primary @click="toggle"
                                          class="mt-8 w-full h-14 lg:max-w-xs text-lg">
                            <span v-if="!open">Читать далее</span>
                            <span v-else>Скрыть</span>
                        </x-button-primary>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
