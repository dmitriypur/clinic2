<div class="container">
    @php
        $author = $block->author;
        $hasAuthor = isset($block->payload['author']) && $author;
        $readingTime = $block->page->reading_time_minutes;
    @endphp

    <div class="bg-[linear-gradient(106.43deg,#FFF8F0_52.02%,#FFE9D0_96.07%)] py-6 px-4 md:p-10 rounded-lg md:rounded-3xl">
        <div class="content-block flex flex-col-reverse lg:flex-row-reverse md:items-stretch gap-4 md:gap-14">
            @if($block->has_image)
                <div class="relative z-10 w-full lg:w-3/4 [&_img]:w-full flex items-center">
                    <img src="{{ asset('images/logo-full.svg') }}" alt="Логотип">
                    <div class="absolute z-20 bg-white rounded-xl p-4 [&_p]:leading-6 bottom-0 right-0">
                        <p class="font-semibold !m-0">{{ $author->full_name }}</p>
                        <p>{{ $author->speciality }}</p>
                    </div>
                    <div class="absolute left-1/2 -translate-x-1/2 -bottom-6 lg:-bottom-10 w-full h-64 sm:h-full [&_img]:w-full [&_img]:h-full [&_img]:object-contain lg:[&_img]:object-cover">{{ $block->getResponsiveImage('default', $block->title) }}</div>
                </div>
            @endif

            <div class="[&_h3]:text-xl [&_h3]:font-semibold [&_h3]:mb-2 w-full lg:pl-16">
                @if (!$block->title_hidden)
                    <div class="relative mx-auto mb-6">
                        <img src="{{ asset('images/quotes.svg') }}" alt="Кавычки" class="hidden lg:block absolute -left-16" width="42" height="33">
                        <h2 class="font-semibold text-xl md:text-[28px] text-heading">
                            {{ $block->title }}
                        </h2>
                    </div>
                @endif
                {!! str($block['body_html'])->sanitizeHtml() !!}
            </div>

        </div>
    </div>
</div>