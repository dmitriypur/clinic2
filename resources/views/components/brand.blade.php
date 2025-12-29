<span class="sr-only">{{ $settings->logoTitle() ?? $settings->logoAlt() }}</span>
<img src="{{ asset('images/logo.svg') }}" alt="{{ $settings->logoAlt() }}"
     @if($settings->logoTitle())
         title="{{ $settings->logoTitle() }}"
     @endif
     width="320" height="66">
