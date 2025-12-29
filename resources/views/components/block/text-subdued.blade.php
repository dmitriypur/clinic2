<div class="container">
    @if(!$block->title_hidden)
        <h2 class="text-sm text-subdued mb-4">
            {{ $block->title }}
        </h2>
    @endif
    <div class="bg-white p-6 md:p-9 rounded-lg md:rounded-20">
        @if (!$block->title_hidden)
            <div class="mx-auto px-10 mb-6">
                <h2 class="font-semibold text-xl md:text-2xl text-center text-heading">
                    {{ $block->title }}
                </h2>
            </div>
        @endif
        <div
            class="content text-sm text-interactive/50 [&_li::marker]:text-sm [&_li::marker]:text-interactive [&_em]:text-interactive">
            {!! $block->body_html !!}
        </div>
    </div>
</div>
