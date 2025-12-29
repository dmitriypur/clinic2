<div class="container">
    <div class="flex flex-col md:flex-row md:gap-x-8">
        <div class="bg-white md:rounded-3xl p-4 md:p-9 -ml-4 -mr-4 md:m-0">
            @if(!$block->title_hidden)
                <h2 class="font-semibold text-2xl text-heading [&_span]:font-normal [&_span]:block md:[&_span]:inline">
                    {!! $block->title !!}
                </h2>
            @endif
            <ul class="flex flex-col gap-5 mt-6">
                @foreach($block->payload['advantages'] as $item)
                    <li class="flex items-center w-full">
                        <div class="min-w-12 w-12 h-12 flex items-center justify-center border border-interactive rounded-xl bg-surface-subdued">
                            <img src="{{ url('storage') . '/' . $item['icon'] }}" class="block"
                                 width="26" height="26" alt="{{ $item['alt_image'] ?? '' }}">
                        </div>
                        <p class="text-base/5 md:text-xl/6 ml-4 md:ml-5">{!! $item['text'] !!}</p>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="flex items-center justify-end p-2 w-full mt-7 md:m-0 md:max-w-[560px] bg-white rounded-3xl">
            <picture>
                <source srcset="{{$block->getFirstMediaUrl('pic')}}" media="(max-width: 767px)">
                <source srcset="{{$block->getFirstMediaUrl('bg')}}">
                <img srcset="{{$block->getFirstMediaUrl('bg')}}" class="" alt="{{ $block->title }}">
            </picture>
        </div>
    </div>
</div>
