@push('scripts')
    {!! Clinic::schema()->videoItem($block->title, $block->body_html ?? 'Видео', $block->getFirstMediaUrl('cover'), $block->created_at, $block->getFirstMediaUrl('video')) !!}
@endpush
@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="lg:container">
    <div class="w-full max-w-[850px] 2xl:max-w-[960px] mx-auto">
        <video-new inline-template>
            <div class="aspect-video relative overflow-hidden md:rounded-[25px] md:border-[3px] md:border-[#F5841F]"
                 ref="container">
                <div :class="backdropClassName">
                    <button
                        type="button"
                        v-show="!hideBackdrop"
                        class="absolute z-10 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"
                        @click="togglePlay"
                    >
                        <span class="block w-14 h-14 md:w-[94px] md:h-[94px]">
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 150" fill="none">
                            <circle cx="75" cy="75" r="73.5" fill="#F5841F" stroke="#F5841F" stroke-width="3"/>
                            <path d="M108.791 75L58.1042 104.264L58.1042 45.7359L108.791 75Z" fill="white"/>
                          </svg>
                        </span>
                    </button>
                </div>
                <video v-if="isLoaded" ref="video" class="w-full aspect-video" preload="auto" playsinline=""
                       poster="{{$block->getFirstMediaUrl('cover')  }}"
                       @play="hideBackdrop = true" @pause="hideBackdrop = false" @ended="videoEnded"
                       :controls="hideBackdrop">
                    <source type="video/mp4" :src="isLoaded ? '{{$block->getFirstMediaUrl('video')}}' : ''">
                </video>
            </div>
        </video-new>
    </div>
</div>
