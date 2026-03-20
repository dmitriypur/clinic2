<footer class="bg-surface">
    <div class="container relative py-10 z-10">
        <div class="hidden xl:block absolute -right-20 bottom-0 max-w-[500px] -z-10">
            <picture>
                <source srcset="{{ asset('images/footer-korgi.webp') }}" type="image/webp">
                <img src="{{ asset('images/footer-korgi.png') }}" alt="Веселые девочка и корги в очках и кепке" width="500" height="550" loading="lazy" class="w-full h-auto">
            </picture>
        </div>
        <div class="flex flex-col lg:flex-row lg:justify-between gap-6 md:gap-10">
            <div class="block md:hidden max-w-72 mx-auto">
                <x-brand :settings="$seoSettings"/>
            </div>
            @if ($footerMenu)
                <nav itemscope=""
                     itemtype="http://schema.org/SiteNavigationElement" class="relative order-0  mt-4 md:mt-0">
                    <ul class="grid md:grid-cols-3 lg:grid-cols-none lg:grid-flow-col gap-6 lg:gap-10 text-center md:text-left">
                        @foreach ($footerMenu->items as $item)
                            <li class="first:col-span-full md:first:col-span-1">
                                <a href="{{ $item['data']['url'] }}"
                                   target="{{ $item['data']['target'] ?? '' }}"
                                   @if(!empty($item['data']['download'])) download @endif
                                   class="text-action-primary text-2xl py-1 flex items-center justify-center md:justify-start gap-x-2 lg:p-0 font-semibold md:mb-2"
                                   itemprop="url">
                                        <span
                                            itemprop="name"> {{ $item['label'] }}</span>
                                </a>

                                @if ($item['children'])
                                    <ul class="text-sm">
                                        @foreach ($item['children'] as $child)
                                            @if(!isset($child['data']['custom-attr']))
                                                <li>
                                                    <a href="{{ $child['data']['url'] }}"
                                                       target="{{ $child['data']['target'] ?? '' }}"
                                                       @if(!empty($child['data']['download'])) download @endif
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
                </nav>
            @endif

            <div class="relative flex-auto order-1 lg:order-0">
                <div class="flex items-center md:items-start flex-col gap-2 md:gap-6 pb-10">
                    <div class="flex flex-col">
                        <div class="hidden md:block max-w-72">
                            <x-brand :settings="$seoSettings"/>
                        </div>
                    </div>
                    <div class="-order-1 md:order-1 py-1 bg-[#FFFBF7] md:py-1.5 px-5 w-auto border border-action-primary rounded-md relative before:absolute before:h-full before:w-2 before:orange-gr-nohover before:top-0 before:left-0 before:rounded">
                        <p class="text-[8px] md:text-es font-semibold italic text-action-primary">Клиника сертифицирована <span class="block text-blue-label">Национальным фондом защиты детского зрения</span></p>
                    </div>
                    <rating-badge></rating-badge>
                    <div class="hidden absolute bottom-0 left-0 max-w-[358px] md:max-w-full">
                        <div class="absolute w-max left-full bottom-[21%] -translate-x-[120%] z-10">
                            <x-footer-phone :settings="$settings"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="pt-4 text-center md:text-left">
            <x-copyright :settings="$settings"/>
            <nav class="mt-6 md:mt-4">
                <ul class="text-es flex flex-col md:flex-row flex-wrap md:justify-center gap-x-4 gap-y-1 md:gap-20 [&_li]:shrink-1">
                    <li><a href="/documents" class="text-interactive no-underline hover:text-interactive/60">Политика конфиденциальности</a></li>
                    <li><a href="/sitemap.html" class="text-interactive no-underline hover:text-interactive/60">Карта сайта</a></li>
                    @if(filled($settings->promotion_company) && filled($settings->promotion_company_url))
                        <li class="block"><a href="{{ $settings->promotion_company_url }}" rel="noindex nofollow" target="_blank" class="text-interactive no-underline hover:text-interactive/60">Продвижение сайта - {{ $settings->promotion_company  }}</a></li>
                    @endif
                </ul>
            </nav>
            <p class="text-center text-es text-interactive/50 mt-3">Имеются противопоказания. Необходима консультация специалиста.<br> Информация, представленная на сайте, не является офертой.</p>
        </div>
    </div>
</footer>
