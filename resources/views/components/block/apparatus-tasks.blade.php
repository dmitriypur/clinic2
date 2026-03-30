<div class="container">
    <div class="py-4">
        @if(!$block->title_hidden)
            <div class="max-w-xl mb-8 md:mb-10">
                <h2 class="text-3xl md:text-2xl font-semibold leading-tight text-heading">
                    {{ $block->title }}
                </h2>
            </div>
        @endif

        @if(!empty($block->payload['tasks']))
            <div class="space-y-4 md:space-y-3">
                @foreach($block->payload['tasks'] as $index => $task)
                    <div class="flex items-center md:items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-action-primary text-white font-bold text-lg leading-none flex items-center justify-center shrink-0">
                            {{ $index + 1 }}
                        </div>

                        <div class="pt-1 text-base leading-7 text-heading">
                            {{ $task['text'] ?? '' }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-12 md:mt-16">
            <div class="relative border border-action-primary bg-action-primary-light rounded-20 px-4 pt-0 pb-6 overflow-visible md:px-8 md:py-0 md:min-h-32">
                <div class="flex justify-center -mt-10 md:absolute md:left-8 md:bottom-0 md:mt-0 md:w-40 md:shrink-0">
                    <picture>
                        <source srcset="{{ asset('images/corgy/new-corgy.webp') }}" type="image/webp">
                        <img
                            src="{{ asset('images/corgy/new-corgy.png') }}"
                            alt=""
                            class="w-40 md:w-full h-auto"
                            loading="lazy"
                            decoding="async"
                        >
                    </picture>
                </div>

                <div class="mt-2 text-black text-base leading-6 md:leading-7 text-left md:pl-56 md:pr-6 md:py-8">
                    {!! nl2br(e($block->payload['note_text'] ?? '')) !!}
                </div>
            </div>
        </div>
    </div>
</div>
