<div class="container">
@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

@isset($block->payload['tags'])
    <div is="sticky-tags" inline-template>
        <div ref="placeholder" :style="placeholderStyle">
            <div
                ref="bar"
                :class="{ 'fixed z-30 bg-transparent pb-6': fixed }"
                :style="barStyle"
                class="overflow-x-auto -mr-4 -ml-4 pl-4 md:mx-0 md:pl-0 mb-6 lg:mb-8 no-scrollbar"
            >
                <div class="w-max md:w-full mx-auto bg-white p-4 rounded-full transition-shadow" :class="{ 'shadow-lg': fixed }">
                    <ul class="flex justify-between items-center overflow-hidden w-max md:w-full rounded-full border-2 border-body-gray">
                        @foreach($block->payload['tags'] as $tag)
                            @php($tagHash = parse_url($tag['link'], PHP_URL_FRAGMENT) ?: ltrim($tag['link'], '#'))
                            <li
                                class="shrink-0 flex-auto text-center"
                                :class="{ '[&_a]:text-white [&_a]:bg-action-primary': isActiveTag(@js($tagHash)) }"
                            >
                                <a href="{{Clinic::relativeUrl(url()->current() . $tag['link'])}}"
                                @click.prevent.stop="scrollToTag(@js($tagHash))"
                                class="block w-full h-full min-w-[150px] py-2 px-4 border-r-2 border-body-gray font-bold text-tags md:hover:bg-action-primary hover:text-white"
                                >{{ $tag['title'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endisset
</div>
