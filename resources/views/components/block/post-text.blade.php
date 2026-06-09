<div class="container">
    <div class="{{ !empty($block->payload['bg_block']) ? $block->payload['bg_block'] : 'bg-white' }} py-6 px-4 md:p-10 rounded-lg md:rounded-3xl">
        <div
            @class([
                'content-block flex flex-col-reverse md:items-start gap-4 md:gap-8',
                'md:flex-row' => $block->image_position === 'left',
                'md:flex-row-reverse' => $block->image_position === 'right',
            ])
        >
            @if($block->has_image)
                <div class="relative z-10 md:w-3/4 md:sticky md:top-60 md:self-start [&_img]:w-full overflow-hidden rounded-lg md:rounded-2xl">
                    {{ $block->getResponsiveImage('default', $block->title) }}
                </div>
            @endif

            <div class="[&_h3]:text-xl [&_h3]:font-semibold [&_h3]:mb-2 w-full">
                @if (!$block->title_hidden)
                    <div class="mx-auto mb-6">
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
