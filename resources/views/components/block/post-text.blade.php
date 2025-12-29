<div class="container">
    <div class="{{ !empty($block->payload['bg_block']) ? $block->payload['bg_block'] : 'bg-white' }} py-6 px-4 md:p-9 rounded-lg md:rounded-20">
        @if (!$block->title_hidden)
            <div class="mx-auto px-10 mb-6">
                <h2 class="font-semibold text-xl md:text-2xl text-center text-heading">
                    {{ $block->title }}
                </h2>
            </div>
        @endif

        <div class="md:text-xl content-block flex flex-col-reverse md:block">
            @if($block->getResponsiveImage('default', $block->title) !== null)
                <div class="relative z-10 md:float-right md:min-w-80 md:max-w-md [&_img]:w-full mt-4 md:mt-0 md:ml-8 md:mb-4 overflow-hidden rounded-lg md:rounded-2xl">
                    {{ $block->getResponsiveImage('default', $block->title) }}
                </div>
            @endif
            <div class="[&_h3]:text-xl [&_h3]:font-semibold [&_h3]:mb-2">
                {!! str($block['body_html'])->sanitizeHtml() !!}
            </div>
            <!-- Сброс обтекания -->
            <div class="clear-both"></div>

        </div>
    </div>
</div>
