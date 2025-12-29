@if (!$block->title_hidden)
    <div class="max-w-2xl mx-auto px-10 mb-6 md:mb-16">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div
    class="flex flex-wrap justify-center items-start gap-4 md:gap-8 md:gap-y-16">
    @foreach($block->elements as $index => $item)
        <div
            class="md:w-[calc(33.33333%-1.5rem)] grow flex gap-4 {{ $item['do_not_show_in_modal'] ?? 'items-center' }} even:flex-row-reverse md:even:flex-row flex-nowrap {{ $item['has_extra_info'] ? 'cursor-pointer group' : '' }}"
            @if(!isset($item['do_not_show_in_modal']) || !$item['do_not_show_in_modal'])
                @click="setActiveElementModal('{{ $item['has_extra_info'] }}', '{{ $block->id }}', {{ $index }})"
            @endif
        >
            <picture
                itemscope itemtype="http://schema.org/ImageObject"
                class="w-1/2 lg:w-[184px] lg:h-[184px] border-4 border-transparent rounded-full {{ $subdued ? 'group-hover:border-white' : 'group-hover:border-surface-subdued' }}">
                <span itemprop="name" class="hidden">{{ $item['title'] }}</span>
                {{ $item['responsive_image'] }}
                @if($item['subtitle'])
                    <span itemprop="description"
                          class="hidden">{{ $item['subtitle'] }}</span>
                @endif
            </picture>
            <div
                class="w-1/2 md:w-2/5 group-even:text-right md:group-even:text-left">
                <h3 class="text-heading font-medium text-lg md:text-xl leading-none">
                    <span
                        class="box-decoration-clone border-b border-transparent {{ $item['do_not_show_in_modal'] ?? 'group-hover:border-interactive' }}">{{ $item['title'] }}</span>
                </h3>

                @if(isset($item['do_not_show_in_modal']) && $item['do_not_show_in_modal'] && isset($item['body_html']))
                    <div class="pt-4">
                        {!! $item['body_html'] !!}
                    </div>
                @endisset
            </div>
        </div>
    @endforeach
</div>

<element-modal :open="activeElementModalBlockId == {{ $block->id }}"
               :elements="{{ json_encode($block->elements) }}"
               :current-index="activeElementModalIndex"
               @close="setActiveElementModal(false)"
></element-modal>
