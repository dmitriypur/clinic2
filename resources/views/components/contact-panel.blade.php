<div
    class="rounded-2xl bg-surface p-4 lg:px-4 py-4 w-full lg:w-[390px] flex flex-col gap-4 accessibility:lg:w-[500px]">
    <p class="text-lg leading-5">Чтобы сделать запись на приём или проконсультироваться свяжитесь с нами:</p>
    <div class="flex flex-col gap-4 accessibility:gap-0">
        <a href="tel:{{ $phone }}"
           class="accessibility:lg:m-0 p-2.5 border bg-surface-subdued flex items-center justify-center gap-3 rounded-lg text-2xl font-medium mt-2 [&_svg]:fill-[#C3C9D6] [&_svg]:stroke-[#C3C9D6] [&_svg]:max-w-7">
            <x-icon-phone></x-icon-phone>
            {{ $phone }}
        </a>
        <a href="mailto:{{ $email }}"
           class="py-3 px-6 border bg-surface-subdued flex items-center gap-3 rounded-lg text-lg font-medium [&_svg]:w-5 [&_svg]:h-5">
            <x-icon-mail></x-icon-mail>
            {{ $email }}
        </a>
        <div class="py-2 px-6 border bg-surface-subdued rounded-lg [&_svg]:w-5 [&_svg]:h-5">
            <p class="text-xs text-interactive-50">также мы будем рады вас видеть по адресу:</p>
            <p class="text-lg font-medium flex items-center gap-1 [&_svg]:fill-[#C3C9D6] leading-none">
                <x-icon-map-pin></x-icon-map-pin>{{ $address }}</p>
        </div>
        <div class="py-2 px-6 border bg-surface-subdued rounded-lg">
            <p class="text-xs text-interactive-50">Режим работы:</p>
            <p class="font-medium flex items-center gap-1 [&_svg]:fill-[#C3C9D6]">{{ str_replace('<br>', '', trim($schedule)) }}</p>
        </div>
    </div>
    <div>
        <x-button-primary @click="showCallbackModal(null, 'otpravka-formy')" class="w-full">
            Оставить заявку на приём
        </x-button-primary>
    </div>
    <div class="accessibility:hidden flex items-center justify-center gap-3">
        @if ($socials['youtube'] ?? false)
            <a href="{{ $socials['youtube'] }}" target="_blank" rel="nofollow"
               class="flex items-center bg-gradient-to-b from-[#FF7E7A] to-[#B3221D] w-12 h-12 rounded-full pl-1">
                <span class="text-white [&_svg]:h-10 [&_svg]:w-10">
                    <x-icon-youtube/>
                </span>
            </a>
        @endif

        @if ($socials['telegram'] ?? false)
            <a href="{{ $socials['telegram'] }}" target="_blank" rel="nofollow"
               class="flex items-center bg-gradient-to-b from-[#9ADDFF] to-[#00A8FC] w-12 h-12 rounded-full pl-2">
                <span class="text-white [&_svg]:h-7 [&_svg]:w-7">
                    <x-icon-telegram/>
                </span>
            </a>
        @endif

        @if ($socials['vk'] ?? false)
            <a href="{{ $socials['vk'] }}" target="_blank" rel="nofollow"
               class="flex items-center bg-gradient-to-b from-[#B3D2FF] to-[#3D80E0] w-12 h-12 rounded-full pl-2">
                <span class="text-white [&_svg]:h-7 [&_svg]:w-7">
                    <x-icon-vk/>
                </span>
            </a>
        @endif

        @if ($socials['rutube'] ?? false)
            <a href="{{ $socials['rutube'] }}" target="_blank" rel="nofollow"
               class="flex items-center w-12 h-12 rounded-full">
                <span class="text-white [&_svg]:h-12 [&_svg]:w-12">
                    <x-icon-rutube/>
                </span>
            </a>
        @endif
        @if ($socials['vk_video'] ?? false)
            <a href="{{ $socials['vk_video'] }}" target="_blank" rel="nofollow"
               class="flex items-center w-12 h-12 rounded-full">
                <span class="text-white [&_svg]:h-12 [&_svg]:w-12">
                    <x-icon-vkvideo/>
                </span>
            </a>
        @endif

    </div>
</div>
