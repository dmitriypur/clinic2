<footer class="py-10 bg-surface lg:py-12">
    <div class="container">
        <div class="flex gap-4 lg:gap-10 flex-col-reverse lg:flex-col">
            <div
                class="flex flex-col lg:flex-row gap-6 lg:justify-between lg:items-center">
                <div class="lg:hidden">
                    <rating-badge></rating-badge>
                </div>
                <div class="lg:hidden">
                    Имеются противопоказания. Необходима консультация
                    специалиста
                </div>

                <div
                    class="flex gap-6 justify-between lg:justify-around items-center">
                    <div class="hidden lg:block">
                        <x-brand :settings="$seoSettings"/>
                    </div>
                    <div class="lg:hidden">
                        <x-copyright :settings="$settings"/>
                    </div>

                    <div class="flex lg:hidden items-center gap-2">
                        @if ($settings->youtube)
                            <a href="{{ $settings->youtube }}"
                               target="_blank"
                               rel="nofollow"
                               class="flex items-center bg-[#c4302b] w-8 h-8 rounded-full pl-1">
                                        <span
                                            class="text-white [&_svg]:h-6 [&_svg]:w-6">
                                            <x-icon-youtube/>
                                        </span>
                            </a>
                        @endif
                        @if ($settings->telegram)
                            <a href="{{ $settings->telegram }}"
                               target="_blank"
                               rel="nofollow"
                               class="flex items-center bg-gradient-to-b from-[#37BBFE] to-[#007DBB] w-8 h-8 rounded-full pl-1.5">
                                        <span
                                            class="text-white [&_svg]:h-4 [&_svg]:w-4">
                                            <x-icon-telegram/>
                                        </span>
                            </a>
                        @endif
                        <a href="{{ $settings->vk }}" target="_blank" rel="nofollow"
                           class="flex text-interactive hover:underline items-center gap-2">
                                    <span
                                        class="text-vk [&_svg]:h-8 [&_svg]:w-8">
                                        <x-icon-vk/>
                                    </span>
                        </a>
                    </div>
                </div>

                <div class="lg:hidden">
                    <x-keycraft/>
                </div>

                <div class="hidden lg:block">
                    <rating-badge></rating-badge>
                </div>
                <div class="hidden lg:block">
                    <div class="flex gap-1">
                        <div class="w-[18px] h-[18px] pt-1">
                                    <span
                                        class="inline-flex w-[13px] h-4 text-icon-subdued">
                                        <x-icon-map-pin></x-icon-map-pin>
                                    </span>
                        </div>
                        <div class="font-medium">
                            <a href="/#contacts"
                               class="text-lg/6">{{ $settings->address }}</a>
                            <p class="text-sm">{{ str_replace('<br>', '', trim($settings->schedule)) }}</p>
                            @if($settings->show_special_schedule)
                                <a href="/storage/{{ $settings->special_schedule }}"
                                   class="text-lg pt-1 block font-medium text-interactive"
                                   target="_blank"><span>{{ $settings->special_schedule_title }}</span></a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="hidden lg:block">
                    <x-phone :settings="$settings"/>
                </div>

                <div class="hidden lg:flex gap-4">
                    @if ($settings->youtube)
                        <a href="{{ $settings->youtube }}"
                           target="_blank"
                           rel="nofollow"
                           class="flex items-center bg-[#c4302b] w-8 h-8 rounded-full pl-1">
                                    <span
                                        class="text-white [&_svg]:h-6 [&_svg]:w-6">
                                        <x-icon-youtube/>
                                    </span>
                        </a>
                    @endif
                    @if ($socials['telegram'] ?? false)
                        <a href="{{ $socials['telegram'] }}"
                           target="_blank"
                           rel="nofollow"
                           class="flex items-center bg-gradient-to-b from-[#37BBFE] to-[#007DBB] w-8 h-8 rounded-full pl-1.5">
                                    <span
                                        class="text-white [&_svg]:h-4 [&_svg]:w-4">
                                        <x-icon-telegram/>
                                    </span>
                        </a>
                    @endif
                    <a href="{{ $socials['vk'] ?? '#' }}" target="_blank" rel="nofollow"
                       class="flex text-interactive hover:underline items-center gap-2">
                                <span class="text-vk [&_svg]:h-8 [&_svg]:w-8">
                                    <x-icon-vk/>
                                </span>
                    </a>
                </div>
            </div>
            @if ($footerMenu)
                <nav itemscope=""
                     itemtype="http://schema.org/SiteNavigationElement">
                    <ul class="grid grid-cols-2 lg:grid-cols-none lg:grid-flow-col">
                        @foreach ($footerMenu->items as $item)
                            <li>
                                <a href="{{ $item['data']['url'] }}"
                                   target="{{ $item['data']['target'] }}"
                                   class="text-interactive hover:underline text-sm py-1 block lg:p-0 lg:text-lg lg:font-medium"
                                   itemprop="url">
                                        <span
                                            itemprop="name"> {{ $item['label'] }}</span>
                                </a>

                                @if ($item['children'])
                                    <ul class="text-sm hidden lg:block">
                                        @foreach ($item['children'] as $child)
                                            <li>
                                                <a href="{{ $child['data']['url'] }}"
                                                   class="whitespace-nowrap py-2 block hover:underline font-medium {{ $child['active'] ? 'text-action-primary hover:text-action-primary-hovered' : 'text-interactive hover:text-interactive-hovered' }}">{{ $child['label'] }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif


            <div class="hidden lg:flex justify-between items-end mt-16">
                <div>
                    <div class="text-sm">Имеются противопоказания.
                        Необходима консультация специалиста
                    </div>
                    <x-copyright :settings="$settings"/>
                </div>
                @if(filled($settings->promotion_company) && filled($settings->promotion_company_url))
                    <div class="text-xs">Продвижение сайта - <a
                            href="{{ $settings->promotion_company_url }}"
                            rel="noindex nofollow"
                            target="_blank"
                            class="underline hover:no-underline">{{ $settings->promotion_company  }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</footer>
