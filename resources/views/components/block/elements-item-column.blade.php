<div class="mx-auto px-10 mb-6 md:mb-12">
    <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
        {{ $block->title }}
    </h2>
</div>

<div class="flex flex-wrap justify-center items-start gap-4 md:gap-8">
    @foreach($block->elements as $index => $item)
        <div
            class="basis-[calc(50%-0.5rem)] md:basis-[calc(25%-1.5rem)] items-center flex flex-col {{ $item['has_extra_info'] ? 'cursor-pointer group' : '' }}"
            @click="setActiveElementModal('{{ $item['has_extra_info'] }}', '{{ $block->id }}', {{ $index }})">
            <picture
                itemscope itemtype="http://schema.org/ImageObject"
                class="mb-4 lg:w-52 lg:h-52 border-4 border-transparent rounded-full {{ $subdued ? 'group-hover:border-white' : 'group-hover:border-surface-subdued' }} group-hover:shadow-sm">
                <span itemprop="name" class="hidden">{{ $item['title'] }}</span>
                {{ $item['responsive_image'] }}
                @if($item['subtitle'])
                    <span itemprop="description"
                          class="hidden">{{ $item['subtitle'] }}</span>
                @endif
            </picture>
            <h3 class="text-heading leading-none font-medium text-lg text-center border-b border-transparent group-hover:border-interactive">{{ $item['title'] }}</h3>
            @if($item['subtitle'])
                <p class="text-sm md:text-base text-center">{{ $item['subtitle'] }}</p>
            @endif
        </div>
    @endforeach
</div>

<element-modal :open="activeElementModalBlockId == {{ $block->id }}"
               :elements="{{ json_encode($block->elements) }}"
               :current-index="activeElementModalIndex"
               @close="setActiveElementModal(false)"
></element-modal>
