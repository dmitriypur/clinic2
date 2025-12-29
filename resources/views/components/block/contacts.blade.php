@push('scripts')
    <script>
        window.YANDEX_API_KEY = "{{ $settings->yandex_map_api_key }}";
        window.COORDINATES = "{{ $currentCity->coordinates }}";
    </script>
@endpush
<div>
    @if (!$block->title_hidden)
    <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading mb-16">
        {{ $block->title }}
    </h2>
    @endif

    <div
        class="relative w-full h-[100vw] lg:h-[600px] bg-surface-subdued">
        <app-map coordinates="{{ $currentCity->coordinates }}" pin-url="{{ asset('images/pin.svg') }}"></app-map>
        <div class="container relative hidden lg:block
            ">
            <div class="absolute z-20 top-16 left-4">
                <x-contact-panel/>
            </div>

            <div class="absolute z-20 top-16 right-4">
                <x-app-panel/>
            </div>
        </div>
    </div>
    <div class="lg:hidden border-b pb-4">
        <x-contact-panel/>
    </div>

</div>
