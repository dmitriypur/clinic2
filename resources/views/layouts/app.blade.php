<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset

    <meta property="og:title" content="{{ $title ?? config('app.name') }}"/>
    @if ($description)
        <meta property="og:description" content="{{ $description }}"/>
    @endif
    <meta property="og:image" content="{{ $image }}"/>
    <meta property="og:type" content="website"/>
    <meta property="og:url" content="{{ url()->current() }}"/>

    @if ($settings->favicon)
        <link rel="icon" type="{{ $settings->faviconMimeType() }}"
              href="{{ $settings->favicon }}">
    @else
        <link rel="icon" type="image/png" sizes="32x32"
              href="{{ asset('icon/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16"
              href="{{ asset('icon/favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180"
              href="{{ asset('icon/apple-touch-icon.png') }}">
    @endif
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="mask-icon" href="{{ asset('icon/safari-pinned-tab.svg') }}"
          color="#f5841f">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#f5841f">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'node_modules/glightbox/dist/css/glightbox.css'])

    {{-- Server-side IP detection compliant with 152-FZ --}}
    @php
        $userIp = request()->ip();
        // Резервные проверки для прокси (Cloudflare, Nginx, балансировщики нагрузки)
        // Это гарантирует получение реального IP-адреса, даже если TrustProxies не настроен для конкретного балансировщика нагрузки

        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $userIp = $_SERVER["HTTP_CF_CONNECTING_IP"];
        } elseif (isset($_SERVER["HTTP_X_REAL_IP"])) {
            $userIp = $_SERVER["HTTP_X_REAL_IP"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ips = explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"]);
            $userIp = trim($ips[0]);
        }
    @endphp
    <script>
        window.userIp = "{{ $userIp }}";
    </script>

    @if (isset($seoSettings->header_scripts) && count($seoSettings->header_scripts))
        @foreach ($seoSettings->header_scripts as $script)
            {!! $script['value'] !!}
        @endforeach
    @endif

    @if (isset($currentCity->header_scripts) && count($currentCity->header_scripts))
        @foreach ($currentCity->header_scripts as $script)
            {!! $script['value'] !!}
        @endforeach
    @endif

    <script>
        window.config = @json(Clinic::scriptVariables());
    </script>

    @stack('header-scripts')
</head>

<body
    class="bg-surface-subdued antialiased text-interactive pt-[72px] lg:pt-56 [&_*]:[-webkit-tap-highlight-color]:transparent">
<div id="app" v-cloak>
    @if (isset($seoSettings->scripts) && count($seoSettings->scripts))
        @foreach ($seoSettings->scripts as $script)
            {!! $script['value'] !!}
        @endforeach
    @endif

    @if (isset($currentCity->body_scripts) && count($currentCity->body_scripts))
        @foreach ($currentCity->body_scripts as $script)
            {!! $script['value'] !!}
        @endforeach
    @endif

    <button @click="toTop"
            class="fixed z-50 bottom-28 right-10 bg-surface hover:bg-surface-subdued/50 border rounded-full shadow-md overflow-clip"
            v-show="showToTopButton">
        <x-icon.chevron-up class="h-10 w-10"/>
    </button>

    @if($showHeader)
        <div is="top-bar" inline-template>
            <header id="AppHeader"
                    class="w-full top-0 z-40 py-4 bg-surface lg:py-8 lg:pb-0 fixed group">
                <div class="relative md:container px-4 pr-2 max-w-[100vw]">
                    <div
                        class="flex items-center justify-between w-full lg:flex-row-reverse">
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
                            <div class="pt-2">
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
                            <!--<ul class="mt-4 lg:mt-0 pr-16">
                                <li>
                                    @guest
                                <button @click="showLoginModal"
                                        class="group/link text-interactive lg:text-lg flex items-center gap-4">
                                    <span
                                        class="fill-icon-subdued w-8 h-8">
                                        <x-icon-user-circle/>
                                    </span>
                                    <span
                                        class="border-b border-transparent group-hover/link:border-interactive leading-none">Личный
                                        кабинет</span>
                                </button>
@else
                                <a href="{{ route('profile.show', [], false) }}"
                                           class="group/link text-interactive lg:text-lg flex items-center gap-4">
                                            <span
                                                class="fill-interactive w-8 h-8">
                                                <x-icon-user-circle/>
                                            </span>
                                            <span
                                                class="border-b border-transparent group-hover/link:border-interactive leading-none">{{ auth()->user()->name ?? 'Личный кабинет' }}</span>
                                        </a>
                                    @endguest
                            </li>
                        </ul>-->
                        </div>
                    </div>
                </nav>
            </header>
        </div>
    @endif

    {!! $slot !!}

    @if($showFooter)
        @include('parts.footer-new')
    @endif

    <online-appointment-form :open="callbackModalActive"
                             :phone="callbackModalPhone"
                             :name="callbackModalName"
                             :target="callbackModalTarget"
                             @close="closeCallbackModal"></online-appointment-form>

    @guest
        <login-modal :open="loginModalActive"
                     @close="closeLoginModal"></login-modal>
    @endguest

    <cookie-toast
        cookie-domain="{{ config('session.domain') ?? request()->getHost() }}"
        cookie-name="{{ $cookieConsentName }}" cookie-lifetime="{{ 365 * 20 }}"
        secure="{{ config('session.secure') }}"
        samesite="{{ config('session.same_site') }}">
        Мы используем файлы cookie, чтобы улучшить сайт для Вас.
        Подробнее:
        <a class="text-interactive underline hover:no-underline" target="_blank"
           href="/documents">
            политика конфиденциальности
        </a>
    </cookie-toast>
</div>

@stack('scripts')
@if (isset($seoSettings->scripts) && count($seoSettings->scripts))
        @foreach ($seoSettings->scripts as $script)
            {!! $script['value'] !!}
        @endforeach
@endif

{!! Clinic::schema()->localBusiness($settings) !!}
{!! Clinic::schema()->medicalOrganization($settings) !!}
</body>

</html>
