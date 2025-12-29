@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

@isset($block->payload['tags'])
    <ul class="md:flex flex-wrap justify-center w-full mb-6 lg:mb-8">
        @foreach($block->payload['tags'] as $tag)
            <li class="p-2">
                <a href="{{Clinic::relativeUrl(url()->current() . $tag['link'])}}"
                   class="w-full md:w-auto inline-flex items-center justify-center border border-interactive md:border-[#E5E7EB] rounded-lg md:px-4 md:py-1 py-3 px-6 {{ !$loop->first ? 'btn-gradient border-none text-white' : '' }}"
                >{{ $tag['title'] }}</a>
            </li>
        @endforeach
    </ul>
@endisset
