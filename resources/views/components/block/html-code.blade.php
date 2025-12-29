@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="container">
    <div class="content lg:text-xl text-interactive">
        {!! $block->body_html !!}
    </div>
</div>
