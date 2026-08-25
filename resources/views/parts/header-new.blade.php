<div is="top-bar" inline-template>
    <header id="AppHeader"
            class="w-full top-0 z-40 bg-surface fixed group">

        <div class="flex h-[70px] items-center justify-between border-b-2 border-[#EBF0F3] bg-white px-4 lg:hidden">
            @if (Request::is('/'))
                <div class="w-[256px] max-w-[calc(100vw-72px)]">
                    <img src="{{ asset('images/logo.svg') }}" alt="{{ $seoSettings->logoAlt() }}" class="block h-auto w-full">
                </div>
            @else
                <a href="{{ home_route() }}" class="block w-[256px] max-w-[calc(100vw-72px)]">
                    <img src="{{ asset('images/logo.svg') }}" alt="{{ $seoSettings->logoAlt() }}" class="block h-auto w-full">
                </a>
            @endif

            <button @click="toggle" type="button" class="flex h-8 w-8 shrink-0 items-center justify-center !overflow-visible text-interactive"
                    :aria-expanded="active ? 'true' : 'false'"
                    aria-controls="mobile-header-navigation"
                    :title="active ? 'Скрыть навигацию' : 'Показать навигацию'">
                <span v-if="active" class="block h-8 w-8"><x-icon-cancel/></span>
                <span v-else class="block h-[27px] w-8"><x-icon-bars-3/></span>
            </button>
        </div>

        <div class="relative hidden max-w-[100vw] border-b border-black-subdued p-4 md:container lg:block">
            <div class="flex items-center justify-between w-full">
                <div class="hidden lg:flex items-center gap-4">
                    @if(isset($cities) && count($cities) > 1)
                    <x-city-switcher :cities="$cities" :currentCity="$currentCity" />
                    @endif
                    <x-address-new :cities="$cities ?? []" />
                </div>

                <div class="flex items-center gap-6">
                    <x-phone-new/>
                    <x-button-primary
                        @click="showCallbackModal(null, 'otpravka-formy')"
                        onclick="ym(94302729,'reachGoal','shapka-forma-open')"
                        class="w-[127px] !py-1.5 !px-2 lg:!py-3 lg:!px-6 lg:w-[224px]"
                    >    <span class="lg:hidden">Записаться</span>
                        <span class="hidden lg:inline">Записаться на приём</span>
                    </x-button-primary>
                </div>
            </div>
        </div>
        <div id="mobile-header-navigation" :class="navClassNameNew">
            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 lg:gap-12 w-full">
                @if (Request::is('/'))
                    <div
                        class="hidden lg:block flex-none overflow-hidden">
                        <x-brand :settings="$seoSettings"/>
                    </div>
                @else
                    <a class="hidden lg:block flex-none w-48 lg:w-80 overflow-hidden"
                        href="{{ home_route() }}">
                        <x-brand :settings="$seoSettings"/>
                    </a>
                @endif
                <div class="flex items-center justify-between lg:hidden">
                    @if(isset($cities) && count($cities) > 1)
                    <x-city-switcher :cities="$cities" :currentCity="$currentCity" />
                    @endif
                    <x-address-new :cities="$cities ?? []" />
                    @if ($socials['vk'] ?? false)
                        <div class="mr-2">
                            <a href="{{ $socials['vk'] }}" target="_blank" rel="nofollow"
                                class="">
                                <x-icon-vk-dark/>
                            </a>
                        </div>
                    @endif
                </div>
                 <div class="block lg:hidden">
                    <x-search></x-search>
                </div>
                <nav :class="{ 'relative': searchOpen }">
                    @if ($mainMenu)
                        <x-mega-menu :menu="$mainMenu"/>
                    @endif
                    <x-search-new class="absolute inset-0" v-show="searchOpen"></x-search-new>
                </nav>
                <div class="hidden lg:flex gap-4 items-center">
                    <div class="w-12 h-12 flex items-center justify-center bg-surface-subdued rounded-lg">
                        <x-icon.search
                            fill="none"
                            width="32"
                            height="32"
                            class="cursor-pointer"
                            @click="toggleSearch"
                        />
                    </div>
                    <div class="w-12 h-12 flex items-center justify-center bg-surface-subdued rounded-lg">
                        <accessibility-toggle></accessibility-toggle>
                    </div>
                </div>
                <div class="font-medium flex lg:hidden items-center justify-center gap-2">
                    <div class="w-5 h-5">
                        <a href="tel:{{ $phone }}"
                            class="inline-flex w-5 h-5 text-icon-interactive">
                            <x-icon-phone-new class="lg:fill-current"></x-icon-phone-new>
                        </a>
                    </div>
                    <div class=" font-medium flex flex-col items-end">
                        <a href="tel:{{ $phone }}"
                        class="text-lg/6 font-semibold">{{ $phone }}</a>
                    </div>
                </div>
                <button
                    class="accessibility:hidden lg:hidden text-base/6 font-semibold text-action-primary ml-4 border-b hover:border-action-primary border-transparent"
                    @click="showCallbackFormNew(null, 'otpravka-formy')">
                    Перезвоните мне
                </button>

            </div>
        </div>
    </header>
</div>
