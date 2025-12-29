<div class="mx-auto px-10 mb-6 md:mb-12">
    <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
        {{ $block->title }}
    </h2>
</div>

<div class="flex flex-wrap justify-center items-start gap-4 md:gap-8">
    @foreach($block->images as $item)
        <div
            class="basis-[calc(50%-0.5rem)] md:basis-[calc(25%-1.5rem)] items-center flex flex-col">
            <picture class="mb-4">
                <img
                    src="{{ $block->getFirstMediaUrl($item['uuid'], 'thumb') }}"
                    alt="{{ $item['title'] }}">
            </picture>
            <h3 class="text-heading font-medium text-lg text-center">{{ $item['title'] }}</h3>
            @if($item['subtitle'])
                <p class="text-sm md:text-base text-center">{{ $item['subtitle'] }}</p>
            @endif
        </div>
    @endforeach
</div>
