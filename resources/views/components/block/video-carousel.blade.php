@push('scripts')
    {!! Clinic::schema()->videos($block, $block->getMedia('videos')) !!}
@endpush
<div class="py-5 md:pt-10 md:pb-6 bg-white md:bg-transparent">
    @if (!$block->title_hidden)
        <div class="mx-auto px-10 mb-6">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div class="container px-8 lg:px-4 relative">
        <div class="md:bg-white md:absolute md:w-full md:h-full md:max-h-[420px] rounded-20 md:left-0 md:-bottom-6"></div>
        <div class="video-carousel-swiper swiper md:pt-32">
            <div class="swiper-wrapper lg:grid grid-cols-3 lg:gap-4" >
                @foreach($block->getMedia('videos') as $index => $item)
                    <video-new inline-template>
                    <div class="swiper-slide flex flex-col items-center lg:gap-4 rounded-lg py-4 lg:p-0" ref="container">
                        <div
                            class="relative rounded-lg overflow-hidden w-[200px] h-[412px] p-1.5 bg-no-repeat bg-contain"
                            style="background-image: url('{{asset('images/mobile-device.png')}}');">
                            <video v-if="isLoaded" ref="video" @play="hideBackdrop = true" @pause="hideBackdrop = false" @ended="videoEnded" :controls="hideBackdrop" class="video-item h-full w-full rounded-3xl object-cover">
                                <source type="video/mp4" preload="auto"
                                        :src="isLoaded ? '{{$item->getUrl()}}' : ''">
                            </video>
                            <div v-if="!hideBackdrop" class="absolute cursor-pointer inset-0 flex items-center justify-center z-20" @click="togglePlay">
                                <button class="w-14 h-14 rounded-full orange-gr border-2 border-white pointer-events-none" aria-label="Запустить видео">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 150" fill="none">
                                        <path d="M108.791 75L58.1042 104.264L58.1042 45.7359L108.791 75Z" fill="white"/>
                                    </svg>
                                </button>
                            </div>

                        </div>
                    </div>
                    </video-new>
                @endforeach
            </div>

            <div
                class="relative z-20 video-carousel-swiper-pagination text-center mt-4 md:-mt-4 [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued">
            </div>
        </div>
        <div
            class="absolute [&_>_.swiper-button-disabled]:opacity-0 left-4 lg:left-10 bottom-1/2 -translate-y-1/2 md:translate-y-0 md:bottom-1/3 z-10">
            <div
                class="video-carousel-swiper-prev cursor-pointer hover:text-action-primary">
                <x-icon-angle-left class="stroke-current fill-none w-5 h-9"/>
            </div>
        </div>

        <div
            class="absolute [&_>_.swiper-button-disabled]:opacity-0 right-4 lg:right-10 bottom-1/2 -translate-y-1/2 md:translate-y-0 md:bottom-1/3 z-10">
            <div
                class="video-carousel-swiper-next cursor-pointer hover:text-action-primary">
                <x-icon-angle-right class="stroke-current fill-none w-5 h-9"/>
            </div>
        </div>
    </div>
</div>
