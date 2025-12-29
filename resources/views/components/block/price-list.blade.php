@push('scripts')
    {!! Clinic::schema()->product($title, $description, $block->prices) !!}
@endpush

@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

@if(count($block->prices))
    <div class="md:container">
        <div class="mb-4 md:mb-8">
            @foreach($block->prices as $price)
                <div class="flex justify-between items-center bg-[#F5F8F9] mb-4 md:rounded-2xl overflow-hidden">
                    <div class="bg-white flex-1 flex gap-3 md:gap-7 items-center py-6 px-3 md:px-7 min-h-28">
                        @if(($price['price2'] ?? 0) > 0)
                            <div class="md:hidden">
                                <img src="{{ asset('images/percent.webp') }}" alt="Знак процента — иллюстрация скидки, акции" width="40" height="40" class="min-w-10 w-10">
                            </div>
                            <div class="hidden md:flex items-center justify-center min-w-6 h-6 md:min-w-8 md:h-8 bg-body-gray rounded-md">
                                <x-icon-arrow-short></x-icon-arrow-short>
                            </div>
                        @else
                            <div class="flex items-center justify-center min-w-6 h-6 md:min-w-8 md:h-8 bg-body-gray rounded-md">
                                <x-icon-arrow-short></x-icon-arrow-short>
                            </div>
                        @endif
                        <p class="text-sm lg:text-2xl">{!! $block->elementToSpanWrap($price['item']) !!}</p>
                    </div>
                    <div class="flex flex-col md:flex-row items-center justify-center md:gap-5 text-center font-medium text-xl md:text-3xl w-20 md:w-[273px]">
                        {!! ($price['price2'] ?? 0) > 0 ? '<s class="text-sm md:text-2xl text-interactive/20">' . $price['price2'] . '</s>' : '' !!}
                        
                        @if($price['price_from'] ?? false)
                            <span class="text-sm mr-1">от</span>
                        @endif
                        {{ $price['price1'] }}
                        
                        @if(($price['price2'] ?? 0) > 0)
                            <div class="hidden md:block">
                                <img src="{{ asset('images/percent.webp') }}" alt="Знак процента — иллюстрация скидки, акции" width="48" height="48" class="w-12">
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
