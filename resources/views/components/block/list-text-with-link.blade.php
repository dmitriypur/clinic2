<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto p-4 md:rounded-2xl md:p-6">
            <h2 class="text-center text-2xl font-semibold text-heading md:text-3xl">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    @if($items !== [])
        <div class="grid gap-3 mt-3 md:mt-5">
            @foreach($items as $item)
                <div class="relative flex items-center rounded-xl bg-surface p-4 text-heading">

                    <span class="mr-4 hidden h-10 w-10 shrink-0 items-center justify-center md:flex" aria-hidden="true">
                        <img
                            src="{{ asset('images/document-license.svg') }}"
                            class="h-9 w-7"
                            width="28"
                            height="36"
                            alt=""
                        >
                    </span>

                    <span class="min-w-0 flex-1 break-words text-base font-bold leading-tight {{ $item['url'] ? 'underline md:no-underline' : '' }}">
                        {!! str($item['text'])->sanitizeHtml() !!}
                    </span>

                    @if($item['url'])
                        <x-document-link
                            :href="$item['url']"
                            :label="$item['actionLabel']"
                            :aria-label="$item['actionLabel'].': '.strip_tags($item['text'])"
                            :new-tab="$item['newTab']"
                            mobile="card"
                            desktop-at="md"
                        />
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
