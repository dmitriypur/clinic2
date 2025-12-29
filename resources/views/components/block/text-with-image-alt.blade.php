@if (!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="container px-8 lg:px-4">

    <div class="grid grid-cols-12 items-center">
        <div class="col-span-4 hidden md:block" itemscope
             itemtype="http://schema.org/ImageObject">
            <span itemprop="name" class="hidden">{{ $block->title  }}</span>
            <div
                class="rounded-full w-72 h-72 [&_img]:w-72 [&_img]:h-72 overflow-hidden lg:shadow-lg [&_img]:object-cover">
                {{ $block->getFirstMedia()?->img('main')->attributes(['alt' => $block->title, 'itemprop' => 'contentUrl']) }}
            </div>
            <span itemprop="description"
                  class="hidden">{{ $block->title }}</span>
        </div>
        <div class="col-span-12 md:col-span-7 space-y-6">
            <div class="content">
                {!! $block->body_html !!}
            </div>
        </div>
    </div>
