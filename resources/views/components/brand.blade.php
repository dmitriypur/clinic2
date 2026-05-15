<span class="sr-only">{{ $settings->logoTitle() ?? $settings->logoAlt() }}</span>
<picture>
    <source media="(max-width: 767px)" srcset="{{ asset('images/logo-mobile.svg') }}">
    <img src="{{ asset('images/logo.svg') }}" alt="{{ $settings->logoAlt() }}"
         @if($settings->logoTitle())
             title="{{ $settings->logoTitle() }}"
         @endif
         class="block w-full h-auto"
         width="351" height="72">
</picture>
