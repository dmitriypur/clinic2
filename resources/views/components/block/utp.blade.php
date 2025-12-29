@if (!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="relative">
    <div class="swiper group utp-swiper lg:px-4">
        <div
            class="group-[:not(.swiper-initialized)]:opacity-0 swiper-wrapper">
            @foreach ($block->payload['utp'] as $item)
                @if (!$block->hasMedia($item['uuid']))
                    @continue
                @endif
                <div
                    class="swiper-slide z-20 group overflow-hidden flex h-auto items-center justify-center">
                    <div
                        class="flex flex-col items-center max-w-[340px] text-center gap-3 relative h-full">
                        <div
                            class="relative w-40 lg:w-52">
                            <picture itemscope
                                     itemtype="http://schema.org/ImageObject"
                                     class="block justify-center w-full pointer-events-none [&_img]:w-full rounded-full overflow-hidden">
                            <span itemprop="name"
                                  class="hidden">{{ $item['title'] }}</span>
                                <span
                                    class="w-full">
                                    {{ $block->getResponsiveImage($item['uuid'], $item['title']) }}
                                </span>

                            </picture>
                        </div>
                        <div
                            class="text-lg/tight lg:leading-normal font-medium mt-1 utp-title lg:!h-auto">{{ $item['title'] }}</div>
                        <div
                            class="leading-tight">{!! $item['body_html'] !!}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div
            class="[&_>_.swiper-button-disabled]:opacity-0 hidden lg:block absolute right-auto left-0 top-1/2 -translate-y-1/2 z-10">
            <div
                class="utp-swiper-prev cursor-pointer hover:text-action-primary">
                <x-icon-angle-left class="stroke-current fill-none w-5 h-9"/>
            </div>
        </div>

        <div
            class="[&_>_.swiper-button-disabled]:opacity-0 hidden lg:block absolute left-auto right-0 top-1/2 -translate-y-1/2 z-10">
            <div
                class="utp-swiper-next cursor-pointer hover:text-action-primary">
                <x-icon-angle-right class="stroke-current fill-none w-5 h-9"/>
            </div>
        </div>
    </div>

</div>
