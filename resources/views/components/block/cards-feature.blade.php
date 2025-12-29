<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto px-10 mb-6 md:mb-12">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div class="grid md:grid-cols-{{ !empty($block->payload['count_col']) ? $block->payload['count_col'] : 2 }} gap-2.5 md:gap-4 lg:gap-y-5 {{ !str_contains(url()->current(), 'services') ? 'lg:gap-x-6' : 'lg:gap-x-16' }}">
        @foreach($block->services as $service)
            <x-feature-card :service="$service"></x-feature-card>
        @endforeach
    </div>

</div>
