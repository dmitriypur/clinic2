@php
    $items = collect($block->payload['items'] ?? [])->filter(fn ($item) => !empty($item['title']) || !empty($item['body_html']))->values();
@endphp

@if($items->isNotEmpty())
    <div class="container">
        @if(!$block->title_hidden)
            <div class="mx-auto mb-6 max-w-4xl md:mb-12">
                <h2 class="text-center text-2xl font-semibold leading-tight text-heading md:text-4xl">
                    {{ $block->title }}
                </h2>
            </div>
        @endif

        <div class="reception-steps-grid grid gap-4 md:grid-cols-3 md:gap-6">
            @foreach($items as $index => $item)
                <article class="relative overflow-hidden rounded-3xl bg-white px-6 py-6 md:pb-12 md:min-h-[410px]">
                    <div class="absolute inset-x-0 -bottom-20 flex justify-center text-action-primary/[0.08] pointer-events-none">
                        <svg viewBox="0 0 78 72" aria-hidden="true" class="translate-y-12 md:translate-y-10 w-full h-auto">
                            <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M38.8942 21.9445V21.9527C44.136 21.9848 48.8578 24.1814 53.7235 27.9595C58.582 31.7317 63.5745 37.0725 69.3657 43.3883C60.2087 53.6507 54.0545 59.2989 47.8446 62.1246C47.5531 62.257 47.2633 62.3826 46.9757 62.5021C48.4281 56.6959 59.3107 51.1522 56.6624 48.0845C53.0765 44.7212 49.4904 55.2454 39.7112 55.4625C29.7148 56.4388 24.825 45.5897 22 48.0845C18.5231 51.156 29.6063 56.0057 31.4537 62.9496C31.481 62.9665 31.5086 62.9831 31.5365 63C31.5309 62.9979 31.5253 62.9957 31.5199 62.9938C26.7827 61.1825 22.6609 57.7163 18.8964 53.9287C17.0147 52.0351 15.2246 50.0633 13.4918 48.1801C11.7865 46.327 10.1355 44.5589 8.51177 43.0396C14.3638 36.8103 19.3852 31.4133 24.2534 27.6334C29.0767 23.8888 33.7378 21.742 38.8942 21.9445ZM39.1324 32.6772C34.0914 32.6772 30.0049 36.7584 30.0049 41.7917C30.005 46.8253 34.0915 50.9052 39.1324 50.9052C44.1732 50.9051 48.2598 46.8253 48.2598 41.7917C48.2598 36.7584 44.1732 32.6773 39.1324 32.6772Z"/>
                            <path fill="currentColor" d="M74.327 32.9858C75.1565 32.2012 76.4585 32.2151 77.2712 33.028C78.0561 33.8139 78.1039 35.0638 77.4151 35.9054L77.267 36.0679L71.7309 41.5767L69.0819 38.9247L68.7205 38.5636L74.2877 33.0239L74.2918 33.0187L74.3219 32.9899L74.327 32.9858Z"/>
                            <path fill="currentColor" d="M0.74193 32.9395C1.55291 32.2387 2.77214 32.2529 3.56798 33.0023L9.28637 38.3867L6.35779 41.4821L6.01917 41.1622L0.673583 36.1286L0.667369 36.1234L0.637338 36.0946L0.634231 36.0905C-0.189345 35.2749 -0.21637 33.9371 0.58556 33.0887L0.74193 32.9395Z"/>
                            <path fill="currentColor" d="M13.3851 18.6474C14.2684 17.8896 15.5968 17.9866 16.3613 18.8737L21.4874 24.8249L18.2564 27.5973L17.9509 27.2414L13.1583 21.6801L13.1542 21.6739L13.1262 21.641L13.1242 21.6369C12.3866 20.7356 12.5038 19.4036 13.3851 18.6474Z"/>
                            <path fill="currentColor" d="M60.3915 18.6577C61.0985 17.8173 62.3084 17.6662 63.1916 18.2658L63.3635 18.3954L63.5209 18.5415C64.2665 19.305 64.3422 20.5277 63.6607 21.3818L63.6566 21.3869L63.6297 21.4198L63.6255 21.425L58.5999 27.4029L55.3358 24.6696L60.3915 18.6577Z"/>
                            <path fill="currentColor" d="M39.2328 9.00926C40.2869 9.11116 41.1449 10.0003 41.1507 11.1284L41.1911 18.9632L40.7137 18.9653L36.9504 18.9859L36.91 11.1511V11.0945C36.9339 9.91747 37.8868 9.00629 39.0184 9L39.2328 9.00926Z"/>
                        </svg>
                    </div>

                    <div class="relative z-10 flex h-full flex-col">
                        <div class="mx-auto inline-flex min-h-10 items-center justify-center rounded-full bg-blue-500 px-5 py-2 text-base font-medium leading-none text-white">
                            Этап №{{ $index + 1 }}
                        </div>

                        <h3 class="mt-5 text-center text-xl font-semibold leading-tight text-heading md:mt-6">
                            {{ $item['title'] ?? '' }}
                        </h3>

                        <div class="mt-5 text-heading [&_li]:ml-5 [&_li]:list-disc [&_ul]:space-y-1">
                            {!! str($item['body_html'] ?? '')->sanitizeHtml() !!}
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
@endif
