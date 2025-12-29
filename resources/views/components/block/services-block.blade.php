<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto px-10 mb-6 md:mb-12">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-2.5 md:gap-4 lg:gap-y-5 {{ !str_contains(url()->current(), 'services') ? 'lg:gap-x-6' : 'lg:gap-x-16' }}">
        @foreach($block->services as $service)
            <x-service-card :service="$service"></x-service-card>
        @endforeach
    </div>

    @if(!str_contains(url()->current(), 'services'))
        <div class="flex justify-center w-full mt-5 md:mt-10">
            <a href="{{ city_route('pages.show') }}/services"
               class="p-3 md:p-4 text-center btn-gradient font-semibold text-white rounded-lg w-full max-w-[450px] md:text-xl">Смотреть все</a>
        </div>
    @endif

</div>
