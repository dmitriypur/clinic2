@push('scripts')
    {!! Clinic::schema()->product($title, $description, count($block->prices) ? $block->prices[0]['price1'] : '') !!}
@endpush

@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

@if(count($block->prices))
    <div class="container">
        <div class="mb-4 md:mb-8">
            @foreach($block->prices as $price)
                @push('scripts')
                    {!! Clinic::schema()->offer($price['item'], $price['price1']) !!}
                @endpush
                <div
                    class="lg:text-[22px] text-interactive flex justify-between items-start border-b py-4 last:border-transparent space-x-8">
                    <p>{{ $price['item'] }}</p>
                    <div
                        class="font-medium whitespace-nowrap">{{ $price['price1'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="text-subdued flex gap-2">
        <span>
            <x-icon-info/>
        </span>
            <span>Цены действительны на {{ now()->format('d.m.Y') }} г. Не является публичной офертой.</span>
        </div>
    </div>
@endif
