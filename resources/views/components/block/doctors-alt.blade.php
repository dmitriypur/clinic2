@if (!$block->title_hidden)
    <div class="mx-auto px-10 mb-5 md:mb-10">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="-mx-4 lg:mx-0">
    <div class="relative">
        <div class="swiper doctors-alt-swiper px-4">
            <div class="swiper-wrapper">
                @foreach ($block->doctors as $index => $doctor)
                    @push('scripts')
                        {!! Clinic::schema()->physician($doctor) !!}
                    @endpush
                    <div class="swiper-slide mb-2 !h-auto">
                        <x-doctor-card-alt :doctor="$doctor"></x-doctor-card-alt>
                    </div>
                @endforeach

            </div>
        </div>
        <a href="{{ city_route('pages.show', ['handle' => 'doctors']) }}" class="text-base font-semibold text-center md:text-right block text-action-primary underline mt-4 md:mt-6">Все врачи</a>
        <div
            class="lg:block lg:absolute left-0 right-0 xl:-left-12 xl:-right-12 lg:top-1/2 lg:-translate-y-1/2 mt-4 lg:mt-0 z-0">
            <div class="[&_>_.swiper-button-disabled]:opacity-0 flex justify-center gap-10 lg:justify-between">
                <div
                    class="doctors-swiper-prev cursor-pointer hover:text-action-primary bg-surface lg:bg-transparent [&_svg]:h-3.5 lg:[&_svg]:h-auto flex items-center justify-center w-8 h-8 lg:w-auto lg:h-auto rounded-full lg:p-4 -ml-2">
                    <x-icon-angle-left class="stroke-current fill-none w-5 h-9"/>
                </div>

                <div class="relative md:hidden">
                    <div
                        class="block doctors-alt-swiper-pagination text-center [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued"></div>
                </div>
                <div
                    class="doctors-swiper-next cursor-pointer hover:text-action-primary bg-surface lg:bg-transparent [&_svg]:h-3.5 lg:[&_svg]:h-auto flex items-center justify-center w-8 h-8 lg:w-auto lg:h-auto rounded-full lg:p-4 -mr-2">
                    <x-icon-angle-right class="stroke-current fill-none w-5 h-9"/>
                </div>
            </div>
        </div>
    </div>
   



</div>
