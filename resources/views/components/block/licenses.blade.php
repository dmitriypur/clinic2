<div class="container">
    <div class="bg-surface rounded-2xl pt-10 pb-4 px-2.5 md:p-10 w-full mx-auto">
        <div class="w-full md:grid grid-cols-12 gap-x-6 accessibility:block">
            <div class="hidden md:block col-span-4 relative flex accessibility:hidden">
                <img src="{{ asset('images/folder.webp') }}" alt="Иллюстрация папки с медицинскими документами" width="400" height="280" class="max-w-72 absolute right-0">
                <img src="{{ asset('images/corgi-lupa.webp') }}" alt="Веселая корги в очках и с лупой" width="156" height="177" class="max-w-72 absolute left-0 z-10">
            </div>
            <div class="md:col-span-8">
                <div class="px-4 md:px-0 lg:pr-20 xl:pr-40">
                    <h2 class="text-center md:text-left font-semibold text-2xl md:text-3xl text-heading">
                        {{ $block->title }}
                    </h2>

                    <p class="text-center md:text-left text-sm font-medium md:text-lg md:text-xl text-interactive/60 mt-4 max-w-3xl">Мы работаем строго в рамках действующего законодательства и на основании официальных медицинских лицензий, выданных Минздравом РФ.</p>

                    <img src="{{ asset('images/folder.webp') }}" alt="Иллюстрация папки с медицинскими документами" width="400" height="280" class="md:hidden mt-5">
                </div>

                <div class="w-full mt-12 md:mt-6">
                    <x-button-primary
                        class="text-lg md:text-xl h-14 md:h-16 w-full md:w-[430px]"
                        @click="$refs.lightbox.showImage(0)">
                        Посмотреть лицензии
                    </x-button-primary>
                </div>
            </div>
        </div>
    </div>
</div>

@if($block->licenses)
    <light-box ref="lightbox" :media="{{ json_encode($block->licenses) }}"
               :show-light-box="false"
               :show-thumbs="false"></light-box>
@endif
