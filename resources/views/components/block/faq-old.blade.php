@push('scripts')
    {!! Clinic::schema()->faq($block->payload['faq']) !!}
@endpush

<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto px-10 mb-6 md:mb-12">
            <h2 class="font-semibold text-2xl md:text-3xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <dl class="space-y-5 text-interactive">
        @foreach($block->payload['faq'] as $item)
            <faq inline-template>
                <details class="border p-4 md:p-6 rounded-lg md:rounded-none">
                    <summary class="flex items-center gap-4 cursor-pointer"
                             @click.self="toggle">
                        <div class="aspect-square h-16 w-16 bg-cover"
                             style="background-image: url('storage/{{ $item['icon'] }}')"></div>
                        <div
                            class="leading-none md:text-xl font-medium select-none"
                            @click.self="toggle">{{ $item['question'] }}</div>
                        <button :class="className">+</button>
                    </summary>
                    <div v-show="open"
                         class="pl-20 pt-2 content select-none">{!! $item['answer_html'] !!}</div>
                </details>
            </faq>
        @endforeach
    </dl>
</div>
