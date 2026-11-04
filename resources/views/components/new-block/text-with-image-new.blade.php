@if (data_get($block->payload, 'layout_variant') === 'service')
    <div class="container">
        <div class="service-text-image grid items-center gap-10 md:grid-cols-2">
            <div class="min-w-0 text-base leading-[1.3] text-heading [&_h2]:mb-3 [&_h2]:text-center [&_h2]:text-2xl [&_h2]:font-semibold [&_h2]:leading-[1.2] [&_h2:not(:first-child)]:mt-6 [&_h3]:mb-3 [&_h3]:text-center [&_h3]:text-2xl [&_h3]:font-semibold [&_h3]:leading-[1.2] [&_h3:not(:first-child)]:mt-6 [&_p+p]:mt-5 md:[&_h2]:text-left md:[&_h2]:text-[34px] md:[&_h3]:text-left md:[&_h3]:text-[34px]">
                @if (!$block->title_hidden && filled($block->title))
                    <h2>{{ $block->title }}</h2>
                @endif
                {!! str($block['body_html'])->sanitizeHtml() !!}
            </div>
            @php($image = $block->getResponsiveImage('default', $block->title, 'text-image')?->lazy())
            @if ($image !== null)
                <div @class(['min-w-0 overflow-hidden rounded-[24px] aspect-[358/260] md:aspect-auto md:h-[460px] [&_img]:h-full [&_img]:w-full [&_img]:object-cover', 'md:order-first' => data_get($block->payload, 'image_position', 'right') === 'left'])>
                    {{ $image }}
                </div>
            @endif
        </div>
    </div>
@else
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
            @php($image = $block->getResponsiveImage('default', $block->title, 'text-image')?->lazy())
            @if($image !== null)
                <div class="w-full h-full [&_img]:w-full [&_img]:h-auto">
                    {{ $image }}
                </div>
            @endif
        </div>
    </div>
</div>
@endif
