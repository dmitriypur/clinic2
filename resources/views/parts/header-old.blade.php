<div is="top-bar" inline-template>
    <header id="AppHeader"
            class="w-full top-0 z-40 py-4 bg-surface lg:py-8 lg:pb-0 fixed group">
        <div class="relative md:container px-4 pr-2 max-w-[100vw]">
            <div class="flex items-center justify-between w-full lg:flex-row-reverse">
                <button @click="toggle" class="md:hidden"
                        :title="active ? 'Скрыть навигацию' : 'Показать навигацию'">
                    <span class="inline-flex text-interactive">
                        <span v-if="active"
                              class="pt-1 w-5 h-4 lg:w-[33px] lg:h-[27px] lg:pr-2">
                            <x-icon-cancel/>
                        </span>
                        <span v-else
                              class="pt-1 w-5 h-4 lg:w-[33px] lg:h-[27px]">
                            <x-icon-bars-3/>
                        </span>
                    </span>
                </button>

                <div class="hidden lg:flex justify-end gap-5 w-full px-10 md:pr-0">
                    <div class="hidden pt-2">
                        <x-city-switcher :cities="$cities" :currentCity="$currentCity" />
                    </div>
                    <x-address/>
                    <div>
                        <x-button-primary
                            @click="showCallbackModal(null, 'otpravka-formy')"
                            onclick="ym(94302729,'reachGoal','shapka-forma-open')"
                            class="w-full"
                        >Записаться на приём
                        </x-button-primary>
                        <div class="flex items-center gap-x-16 mt-5">
                            <x-phone/>
                            <accessibility-toggle></accessibility-toggle>
                        </div>
                    </div>
                </div>

                <div class="flex-none w-48 lg:w-full lg:max-w-lg lg:mr-3">
                    @if (Request::is('/'))
                        <div
                            class="flex-none overflow-hidden">
                            <x-brand :settings="$seoSettings"/>
                        </div>
                    @else
                        <a class="flex-none block w-48 lg:w-80 overflow-hidden"
                           href="{{ home_route() }}">
                            <x-brand :settings="$seoSettings"/>
                        </a>
                    @endif
                    <div class="hidden lg:block">
                        <x-search></x-search>
                    </div>

                </div>

                <div class="lg:hidden flex gap-4 items-center">
                   <a href="tel:{{ $phone }}">
                        <div class="w-[18px] h-[18px] pt-0.5">
                            <span
                                class="inline-flex w-[18px] h-[18px] text-icon-subdued">
                                <x-icon-phone></x-icon-phone>
                            </span>
                        </div>
                    </a>
                    <a href="{{ city_route('pages.show', ['handle' => 'kontakty']) }}" class="inline-flex lg:hidden"
                       title="Посмотреть карту">
                        <span
                            class="inline-flex w-6 h-5 lg:w-[px] lg:h-[27px] text-icon-subdued">
                            <x-icon-map-pin></x-icon-map-pin>
                        </span>
                    </a>
                </div>
            </div>
        </div>
        <nav :class="navClassName">
            <div class="container">
                <div
                    class="flex flex-col-reverse lg:flex-row lg:justify-between lg:items-center gap-6">
                    <div class="lg:hidden mb-4">
                        <x-button-secondary
                            @click="showCallbackModal(null, 'otpravka-formy')"
                            onclick="ym(94302729,'reachGoal','shapka-call-open')
                        ">
                            Перезвоните мне
                        </x-button-secondary>
                    </div>

                    <div class="lg:hidden">
                        <div class="flex gap-1">
                            <div class="w-[18px] h-[18px] pt-0.5">
                                <span
                                    class="inline-flex w-[18px] h-[18px] text-icon-subdued">
                                    <x-icon-phone></x-icon-phone>
                                </span>
                            </div>
                            <a href="tel:{{ $phone }}"
                               class="text-lg/6 font-medium">{{ $phone }}</a>
                        </div>
                    </div>

                    <div class="lg:hidden">
                        <div class="flex gap-1">
                            <div class="w-[18px] h-[18px] pt-1">
                                <span
                                    class="inline-flex w-[13px] h-4 text-icon-subdued">
                                    <x-icon-map-pin></x-icon-map-pin>
                                </span>
                            </div>
                            <div class="font-medium">
                                <div class="mb-4">
                                    <x-city-switcher :cities="$cities" :currentCity="$currentCity" />
                                </div>
                                <a class="text-lg/6"
                                   href="{{ city_route('pages.show', ['handle' => 'kontakty']) }}">{{ $address }}</a>
                                <p class="text-sm">{{ str_replace('<br>', '', trim($schedule)) }}</p>

                                @if ($showSpecialSchedule ?? false)
                                    <a href="/storage/{{ $specialSchedule }}"
                                       class="py-2 block after:absolute after:bottom-0 after:left-0 after:h-[3px] after:w-full font-medium text-interactive hover:after:bg-interactive"
                                       target="_blank"><span>{{ $specialScheduleTitle }}</span></a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="lg:hidden">
                        <x-button-primary
                            @click="showCallbackModal(null, 'otpravka-formy')"
                            onclick="ym(94302729,'reachGoal','shapka-forma-open')
                        ">
                            Записаться на приём
                        </x-button-primary>
                    </div>
                    @if ($mainMenu)
                        <x-mega-menu :menu="$mainMenu"/>
                    @endif
                    <div class="block lg:hidden">
                        <x-search></x-search>
                    </div>
                </div>
            </div>
        </nav>
    </header>
</div>
