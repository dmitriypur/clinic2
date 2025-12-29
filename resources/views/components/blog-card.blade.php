<div class="px-3 pt-3 pb-6 bg-white rounded-2xl h-full">
    <div
        class="relative rounded-xl overflow-hidden border border-interactive/40 h-56 [&_img]:w-full [&_img]:h-full [&_img]:object-cover">
        <a href="{{ $item->getUrl() }}">
            @if($item->getResponsiveImage('default', $item->title, 'main') !== null)
                {{ $item->getResponsiveImage('default', $item->title, 'main') }}
            @else
                <picture>
                    <source type="image/webp" class="lazy" srcset="{{ asset('images/no-image.webp') }}">
                    <img src="{{ asset('images/no-image.jpg') }}" alt="{{ $item['title'] }}" width="342" height="222">
                </picture>
            @endif
        </a>

        @if(count($item->tags))
            <div class="absolute left-2 bottom-2 h-auto flex flex-wrap gap-2">
                @foreach($item->tags as $tag)
                    <a href="{{ city_route('tag.index', ['handle' => $tag->handle]) }}"
                       class="bg-white/90 text-es rounded-sm px-2 py-1 leading-none">{{ $tag->title }}</a>
                @endforeach
            </div>
        @endif
    </div>

    <p class="text-sm text-interactive/40 mt-3 mb-2">{{ $item->created_at->format('d.m.Y') }}</p>
    <hr>
    <a href="{{ $item->getUrl() }}">
        <span class="text-lg font-semibold mt-2 leading-tight">{{ $item->title }}</span>
    </a>

    @if(!empty($item->body_html))
        <div class="text-sm mt-2">{!! str(reduction($item->body_html, 80))->sanitizeHtml() !!}</div>
    @endif
</div>
