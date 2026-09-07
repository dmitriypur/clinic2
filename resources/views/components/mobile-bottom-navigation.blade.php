<div is="mobile-bottom-navigation" inline-template class="lg:hidden">
    <div>
        <nav
            class="fixed inset-x-0 bottom-0 z-[46] grid h-[calc(60px+env(safe-area-inset-bottom))] grid-cols-5 rounded-t-lg bg-white px-2 pb-[env(safe-area-inset-bottom)] text-[#1F3462] shadow-[0_-2px_10px_rgba(31,52,98,0.08)]"
            aria-label="Основная мобильная навигация"
        >
            @isset($items['services'])
                <a
                    href="{{ $items['services']['data']['url'] }}"
                    target="{{ $items['services']['data']['target'] ?? '' }}"
                    @if($items['services']['active']) aria-current="page" @endif
                    class="mobile-bottom-navigation__item {{ $items['services']['active'] ? 'text-[#F77C27]' : '' }}"
                >
                    <span class="h-8 w-8" aria-hidden="true">
                        <x-mobile-nav.icon-services />
                    </span>
                    <span>Услуги</span>
                </a>
            @else
                <span data-mobile-nav-slot="services-placeholder" aria-hidden="true"></span>
            @endisset

            @isset($items['doctors'])
                <a href="{{ $items['doctors']['data']['url'] }}"
                   @if($items['doctors']['active']) aria-current="page" @endif
                   class="mobile-bottom-navigation__item {{ $items['doctors']['active'] ? 'text-[#F77C27]' : '' }}">
                    <span class="h-8 w-8" aria-hidden="true">
                        <x-mobile-nav.icon-doctors />
                    </span>
                    <span>{{ $items['doctors']['mobile_label'] ?? $items['doctors']['label'] }}</span>
                </a>
            @else
                <span data-mobile-nav-slot="doctors-placeholder" aria-hidden="true"></span>
            @endisset

            <button type="button" class="mobile-bottom-navigation__booking" @click="openBookingWidget">
                <span class="absolute -top-[22px] flex h-16 w-16 items-center justify-center rounded-full bg-white">
                    <span class="flex h-[46px] w-[46px] items-center justify-center rounded-full bg-[#3981F1] text-white">
                        <span class="h-6 w-6"><x-mobile-nav.icon-booking /></span>
                    </span>
                </span>
                <span class="absolute bottom-[5px]">Записаться</span>
            </button>

            @isset($items['prices'])
                <a href="{{ $items['prices']['data']['url'] }}"
                   @if($items['prices']['active']) aria-current="page" @endif
                   class="mobile-bottom-navigation__item {{ $items['prices']['active'] ? 'text-[#F77C27]' : '' }}">
                    <span class="h-8 w-8" aria-hidden="true"><x-mobile-nav.icon-prices /></span>
                    <span>{{ $items['prices']['label'] }}</span>
                </a>
            @else
                <span data-mobile-nav-slot="prices-placeholder" aria-hidden="true"></span>
            @endisset

            @isset($items['contacts'])
                <a href="{{ $items['contacts']['data']['url'] }}"
                   @if($items['contacts']['active']) aria-current="page" @endif
                   class="mobile-bottom-navigation__item {{ $items['contacts']['active'] ? 'text-[#F77C27]' : '' }}">
                    <span class="h-8 w-8" aria-hidden="true"><x-mobile-nav.icon-contacts /></span>
                    <span>{{ $items['contacts']['label'] }}</span>
                </a>
            @else
                <span data-mobile-nav-slot="contacts-placeholder" aria-hidden="true"></span>
            @endisset
        </nav>
    </div>
</div>
