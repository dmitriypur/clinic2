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
    class="bg-surface-subdued antialiased text-interactive pt-[72px] lg:pt-40 [&_*]:[-webkit-tap-highlight-color]:transparent">
<div id="app" v-cloak class="overflow-hidden">
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

    @php
        $headerView = $headerView ?? 'parts.header-new';
    @endphp

    @if($showHeader)
        @include($headerView)
    @endif

    {!! $slot !!}

    @if($showFooter)
        @include('parts.footer')
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

    <city-confirmation-modal></city-confirmation-modal>

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
