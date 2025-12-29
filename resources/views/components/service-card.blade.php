<div
    class="rounded-xl {{!empty($service['dark_card']) ? 'bg-interactive/85' : 'bg-white'}} py-4 px-4 md:px-6 md:rounded-20 flex gap-4 md:transition-transform md:hover:scale-105 md:[&_.icon-block]:hover:opacity-100 accessibility-hide-animation">

    @if($service['services'])
        <a href="{{ city_route('pages.show', ['handle' => $service['services']]) }}" class="accessibility-hide">
            <div class="rounded-xl overflow-hidden relative min-w-24 max-w-24 md:min-w-48 md:max-w-48">
                <div
                    class="icon-block absolute p-3 bg-white/50 rounded-lg backdrop-blur-sm top-4 right-6 z-10 opacity-0 transition-opacity">
                    <x-icon-arrow-diagonal></x-icon-arrow-diagonal>
                </div>
                {!! $service['image_html'] !!}
            </div>
        </a>
    @else
        <div class="rounded-xl overflow-hidden relative min-w-24 max-w-24 md:min-w-48 md:max-w-48 accessibility-hide">
            {!! $service['image_html'] !!}
        </div>
    @endif
    <div class="flex flex-col">
        @if($service['services'])
        <a href="{{ city_route('pages.show', ['handle' => $service['services']]) }}" class="mb-3 md:mb-6">
            <p class="text-sm {{!empty($service['dark_card']) ? 'text-white' : ''}} leading-tight md:text-lg lg:text-2xl font-medium">{{ $service['title'] }}</p>
            <p class="text-sm {{!empty($service['dark_card']) ? 'text-white' : 'text-interactive/50'}} leading-tight md:text-lg lg:text-2xl font-medium">{{ $service['subtitle'] }}</p>
        </a>
        @else
            <div class="mb-3 md:mb-6">
                <p class="text-sm {{!empty($service['dark_card']) ? 'text-white' : ''}} leading-tight md:text-lg lg:text-2xl font-medium">{{ $service['title'] }}</p>
                <p class="text-sm {{!empty($service['dark_card']) ? 'text-white' : 'text-interactive/50'}} leading-tight md:text-lg lg:text-2xl font-medium">{{ $service['subtitle'] }}</p>
            </div>
        @endif


        <div class="accessibility-hide mt-auto [&_ul]:flex [&_ul]:flex-wrap [&_ul]:gap-1.5 [&_ul]:md:gap-2 [&_li]:text-[8px] [&_li]:md:text-es [&_li]:font-light [&_li]:leading-none py-0.5 [&_li]:py-0.5 [&_li]:px-4 [&_li]:md:py-1 [&_li]:md:px-6 [&_li]:rounded [&_li]:bg-light-gray [&_li]:shadow-tag-service [&_li]:relative">
            {!! $service['body_html'] !!}
        </div>
    </div>
</div>
