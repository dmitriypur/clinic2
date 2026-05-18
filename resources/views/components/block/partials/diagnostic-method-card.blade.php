@props([
    'item',
    'compact' => false,
])

@php
    $tag = !empty($item['href']) ? 'a' : 'article';
@endphp

<{{ $tag }}
    @if(!empty($item['href']))
        href="{{ $item['href'] }}"
    @endif
    @class([
    'rounded-3xl bg-white transition-all duration-200 hover:border-orange-300 hover:shadow-2xl',
    'block' => !empty($item['href']),
    'border border-transparent p-4 md:p-5' => !$compact,
    'border border-slate-200 p-4 md:px-4 md:py-5' => $compact,
])>
    <div @class([
        'flex gap-4',
        'items-center' => $compact,
    ])>
        @if(!empty($item['image_html']))
            <div @class([
                'shrink-0 overflow-hidden rounded-full bg-slate-100 [&_img]:h-full [&_img]:w-full [&_img]:object-cover',
                'mt-1 h-14 w-14' => !$compact,
                'h-16 w-16 md:h-14 md:w-14 md:self-center' => $compact,
            ])>
                {!! $item['image_html'] !!}
            </div>
        @endif

        <div class="min-w-0 flex-1">
            <h3 @class([
                'font-semibold leading-tight text-heading',
                'text-xl' => !$compact,
                'text-2xl md:text-xl' => $compact,
            ])>
                <span class="decoration-1 underline underline-offset-4">
                    {{ $item['title'] ?? '' }}
                </span>
            </h3>

            @if(!empty($item['body_html']))
                <div @class([
                    'mt-2 text-sm leading-5 text-heading [&_p:not(:last-child)]:mb-3'
                ])>
                    {!! str($item['body_html'])->sanitizeHtml() !!}
                </div>
            @endif
        </div>
    </div>
</{{ $tag }}>
