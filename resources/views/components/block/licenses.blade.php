<div class="container">
    <div class="bg-surface rounded-2xl w-full mx-auto overflow-hidden md:overflow-visible">
        <div class="w-full flex flex-col md:grid grid-cols-12 gap-x-6 accessibility:block bg-gradient-to-tr from-[#65B3EB] via-white via-50% to-white md:bg-none">
            <div class="block order-1 md:order-0 col-span-5 relative flex accessibility:hidden md:bg-gradient-to-r from-[#65B3EB] to-white rounded-l-2xl">
                <img src="{{ asset('images/korgy-lupa.webp') }}" alt="Иллюстрация папки с медицинскими документами" width="400" height="280" class="w-full max-w-[542px] md:absolute left-0 bottom-0">
            </div>
            <div class="md:col-span-7 px-3 py-6 md:py-20 order-0 md:order-1">
                <div class="px-4 md:px-0 lg:pr-20 xl:pr-40">
                    <h2 class="font-semibold text-[28px] md:text-[34px] text-heading">
                        {{ $block->title }}
                    </h2>

                    <p class="font-medium text-interactive/60 mt-4 max-w-3xl">Мы работаем строго в рамках действующего законодательства и на основании официальных медицинских лицензий, выданных Минздравом РФ.</p>
                </div>

                <div class="w-full mt-4 md:mt-6 px-4 md:px-0">
                    <x-button-primary
                        class="h-14 w-full md:w-[225px]"
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
