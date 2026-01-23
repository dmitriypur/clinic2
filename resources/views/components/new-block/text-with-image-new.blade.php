<div class="container">
    <div class="{{ !empty($block->payload['bg_block']) ? $block->payload['bg_block'] : 'bg-white' }} p-4 md:p-10 rounded-3xl md:rounded-20">
        @if (!$block->title_hidden)
            <div class="mx-auto px-10 mb-6">
                <h2 class="font-semibold text-xl md:text-2xl text-center text-heading">
                    {{ $block->title }}
                </h2>
            </div>
        @endif

        <div class="grid items-center gap-2.5 md:gap-10 md:grid-cols-2 gap-4">
            
            <div class="[&_h3]:text-xl [&_h3]:font-semibold [&_h3]:mb-2">
                {!! str($block['body_html'])->sanitizeHtml() !!}
            </div>
            @if($block->getResponsiveImage('default', $block->title) !== null)
                <div class="w-full h-full [&_img]:w-full [&_img]:h-auto">
                    {{ $block->getResponsiveImage('default', $block->title) }}
                </div>
            @endif
        </div>
    </div>
</div>
