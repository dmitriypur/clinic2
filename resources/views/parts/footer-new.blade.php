<footer class="py-10 bg-surface lg:py-7">
    <div class="container">
        <div class="flex flex-col lg:flex-row lg:justify-between">
            @if ($footerMenu)
                <nav itemscope=""
                     itemtype="http://schema.org/SiteNavigationElement" class="relative order-1 lg:order-0  mt-4 md:mt-0">
                    <ul class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-none lg:grid-flow-col gap-6 lg:gap-10">
                        @foreach ($footerMenu->items as $item)
                            <li class="first:col-span-full md:first:col-span-1">
                                <a href="{{ $item['data']['url'] }}"
                                   target="{{ $item['data']['target'] }}"
                                   class="text-action-primary text-sm py-1 flex items-center gap-x-2 lg:p-0 font-semibold"
                                   itemprop="url">
                                        <span
                                            itemprop="name"> {{ $item['label'] }}</span>

                                    <x-icon.chevron-up class="w-4 h-auto fill-action-primary"></x-icon.chevron-up>
                                </a>

                                @if ($item['children'])
                                    <ul class="text-sm">
                                        @foreach ($item['children'] as $child)
                                            @if(!isset($child['data']['custom-attr']))
                                                <li>
                                                    <a href="{{ $child['data']['url'] }}"
                                                       class="whitespace-nowrap py-1 block hover:underline font-medium {{ $child['active'] ? 'text-action-primary hover:text-action-primary-hovered' : 'text-interactive hover:text-interactive-hovered' }}">{{ $child['label'] }}</a>
                                                </li>
                                            @else
                                                <li>
                                                    <p @click="showCallbackModal(null, 'otpravka-formy')"
                                                       class="whitespace-nowrap py-1 block hover:underline font-medium cursor-pointer text-interactive hover:text-interactive-hovered"
                                                       onclick="ym(94302729,'reachGoal','shapka-forma-open')">Записаться на прием</p>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach

                    </ul>

                    @if(filled($settings->promotion_company) && filled($settings->promotion_company_url))
                        <a href="{{ $settings->promotion_company_url }}" rel="noindex nofollow" target="_blank" class="md:hidden text-interactive text-es absolute right-0 bottom-2 font-medium">Продвижение сайта - {{ $settings->promotion_company  }}</a>
                    @endif
                </nav>
            @endif

            <div class="relative flex-auto order-0 lg:order-1">
                <div class="flex md:flex-col gap-2 md:gap-6 items-center md:items-end pb-28 md:pb-32">
                    <div class="flex items-end gap-11">
                        <div class="hidden md:block max-w-48">
                            <x-brand :settings="$seoSettings"/>
                        </div>
                        <rating-badge></rating-badge>
                    </div>
                    <div class="-order-1 md:order-1 py-1 bg-[#FFFBF7] md:py-1.5 px-5 w-auto border border-action-primary rounded-md relative before:absolute before:h-full before:w-2 before:orange-gr-nohover before:top-0 before:left-0 before:rounded">
                        <p class="text-[8px] md:text-es font-semibold italic text-action-primary">Клиника сертифицирована <span class="block text-blue-label">Национальным фондом защиты детского зрения</span></p>
                    </div>
                    <div class="absolute bottom-0 left-0 max-w-[358px] md:max-w-full">
                        <div class="absolute w-max left-full bottom-[21%] -translate-x-[120%] z-10">
                            <x-footer-phone :settings="$settings"/>
                        </div>
                        <img src="{{ asset('images/footer-main.webp') }}" alt="Веселые девочка и корги в очках и кепке" width="600" height="205">
                    </div>
                </div>
            </div>
        </div>
        <div class="pt-4">
            <x-copyright :settings="$settings"/>
            <nav class="mt-6 md:mt-4">
                <ul class="text-es flex flex-wrap md:justify-center gap-x-4 gap-y-1 md:gap-20 [&_li]:shrink-1">
                    <li><a href="{{ city_route('pages.show', ['handle' => 'documents']) }}" class="text-interactive no-underline hover:text-interactive/60">Политика конфиденциальности</a></li>
                    <li><a href="{{ city_route('pages.show', ['handle' => 'sitemap.html']) }}" class="text-interactive no-underline hover:text-interactive/60">Карта сайта</a></li>
                    @if(filled($settings->promotion_company) && filled($settings->promotion_company_url))
                        <li class="hidden md:block"><a href="{{ $settings->promotion_company_url }}" rel="noindex nofollow" target="_blank" class="text-interactive no-underline hover:text-interactive/60">Продвижение сайта - {{ $settings->promotion_company  }}</a></li>
                    @endif
                </ul>
            </nav>
            <p class="hidden md:block text-center text-es text-interactive/50 mt-3">Имеются противопоказания. Необходима консультация специалиста.<br> Информация, представленная на сайте, не является офертой.</p>
        </div>
    </div>
</footer>

