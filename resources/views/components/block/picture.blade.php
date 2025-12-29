@if (!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="container">
    <div style="max-width:{{ $block->payload['width'] }}" class="overflow-hidden [&_img]:md:w-full {{ implode(' ', $block->payload['classes']) }}" itemscope itemtype="http://schema.org/ImageObject">
        <span itemprop="name" class="hidden">{{ $block->payload['image_title']  }}</span>
        {{ $block->getResponsiveImage('default', $block->payload['image_title']) }}
        <span itemprop="description"
              class="hidden">{{ $block->payload['image_title'] }}</span>
    </div>
</div>
