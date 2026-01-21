<div class="container">
@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

@isset($block->payload['tags'])
    <div class="overflow-x-auto -mr-4 -ml-4 pl-4 md:mx-0 md:pl-0 mb-6 lg:mb-8 no-scrollbar">
        <div class="w-max md:w-full mx-auto bg-white p-4 rounded-full">
            <ul class="flex justify-between items-center overflow-hidden md:overflow-visible w-max md:w-full rounded-full md:rounded-none border-2 border-body-gray md:border-0">
                @foreach($block->payload['tags'] as $tag)
                    <li class="shrink-0 flex-auto text-center {{ $loop->first ? '[&_a]:text-white [&_a]:md:text-tags [&_a]:bg-action-primary [&_a]:md:bg-transparent [&_a]:md:border-b-action-primary' : '' }}">
                        <a href="{{Clinic::relativeUrl(url()->current() . $tag['link'])}}"
                        class="block w-full h-full min-w-[150px] py-2 px-4 md:p-2 border-r-2 md:border-r-0 md:border-b-2 border-body-gray font-bold text-tags hover:bg-action-primary hover:md:bg-white hover:text-white md:hover:border-action-primary md:hover:text-action-primary"
                        >{{ $tag['title'] }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endisset
</div>
