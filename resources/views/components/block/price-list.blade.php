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

@php($priceListItems = $block->price_list_items)

@if(count($priceListItems))
    <div class="container">
        <div class="mb-4 lg:mb-8">
            <div class="rounded-3xl bg-white px-3.5 py-6 text-center text-heading lg:px-12 lg:py-8 lg:text-left">
                @foreach($priceListItems as $price)
                    <div @class([
                        'flex flex-col items-center justify-center gap-2 lg:flex-row lg:gap-4',
                        'mb-6 border-b border-dotted border-interactive/40 pb-6 lg:mb-0 lg:border-b-0 lg:pb-0' => !$loop->last,
                        'lg:mt-6' => !$loop->first,
                    ])>
                        <div class="w-full lg:w-auto lg:shrink-0 lg:whitespace-nowrap">
                            <p class="text-xl font-semibold leading-6">
                                {{ $price['title'] }}
                            </p>

                            @if($price['description'])
                                <p class="mt-1 text-base font-normal leading-5">
                                    {{ $price['description'] }}
                                </p>
                            @endif
                        </div>

                        <div class="hidden min-w-0 flex-1 border-t border-dotted border-interactive/30 lg:block"></div>

                        <div class="flex w-full items-center justify-center gap-2 whitespace-nowrap text-xl font-semibold leading-6 lg:w-auto lg:shrink-0 lg:justify-end">
                            @if($price['old_price'])
                                <s class="text-heading/50 lg:text-heading/40">
                                    {{ $price['old_price'] }} ₽
                                </s>
                            @endif

                            <span>
                                @if($price['price_from'])
                                    от&nbsp;
                                @endif

                                {{ $price['price'] }} ₽
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
