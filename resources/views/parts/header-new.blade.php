<div is="top-bar" inline-template>
    <header id="AppHeader"
            class="w-full top-0 z-40 bg-surface fixed group">

        <div class="relative md:container p-4 max-w-[100vw] border-b border-black-subdued">
            <div class="flex items-center justify-between w-full">
                <button @click="toggle" class="lg:hidden"
                        :title="active ? 'Скрыть навигацию' : 'Показать навигацию'">
                    <span class="inline-flex text-interactive">
                        <span v-if="active"
                              class="pt-1 w-5 h-4 lg:w-[33px] lg:h-[27px] lg:pr-2">
                            <x-icon-cancel/>
                        </span>
                        <span v-else
                              class="pt-1 w-5 h-4 lg:w-[33px] lg:h-[27px]">
                            <x-icon-bars-2/>
                        </span>
                    </span>
                </button>
                @if (Request::is('/'))
                    <div
                        class="flex-none w-full max-w-48 overflow-hidden lg:hidden">
                        <x-brand :settings="$seoSettings"/>
                    </div>
                @else
                    <a class="flex-none block w-full max-w-48 overflow-hidden lg:hidden"
                        href="{{ home_route() }}">
                        <x-brand :settings="$seoSettings"/>
                    </a>
                @endif
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
                        class="hidden lg:block w-[224px]"
                    >Записаться на приём
                    </x-button-primary>
                </div>
                <div class="lg:hidden text-gray-300">
                    <x-icon-map-pin width="22" height="22" />
                </div>
            </div>
        </div>
        <div :class="navClassNameNew">
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
                <div class="lg:hidden">
                    @if(isset($cities) && count($cities) > 1)
                    <x-city-switcher :cities="$cities" :currentCity="$currentCity" />
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
                <x-button-primary
                        @click="showCallbackModal(null, 'otpravka-formy')"
                        onclick="ym(94302729,'reachGoal','shapka-forma-open')"
                        class="lg:hidden w-full"
                    >Записаться на приём
                </x-button-primary>

            </div>
        </div>
    </header>
</div>
