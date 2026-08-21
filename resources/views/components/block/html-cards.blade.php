<div class="container html-cards-block">
    @if(! $block->title_hidden)
        <h2 class="mb-6 text-2xl font-semibold text-heading md:mb-12 md:text-4xl">
            {{ $block->title }}
        </h2>
    @endif

    <div class="{{ $layoutClasses }}">
        @foreach($items as $item)
            <div class="{{ $item['classes'] }}">
                {!! $item['html'] !!}
            </div>
        @endforeach
    </div>
</div>
