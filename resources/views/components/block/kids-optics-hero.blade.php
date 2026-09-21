<div
    class="relative aspect-[13/20] w-full overflow-hidden bg-white md:aspect-auto md:h-[440px] lg:h-[450px] xl:h-[492px]"
    aria-labelledby="{{ $heroId }}-title"
>
    <picture class="absolute inset-0 block h-full w-full" aria-hidden="true">
        <source media="(min-width: 768px)" srcset="{{ $desktopImages['avif'] }}" type="image/avif">
        <source media="(min-width: 768px)" srcset="{{ $desktopImages['webp'] }}" type="image/webp">
        <source media="(min-width: 768px)" srcset="{{ $desktopImages['fallback'] }}" type="image/jpeg">
        <source srcset="{{ $mobileImages['avif'] }}" type="image/avif">
        <source srcset="{{ $mobileImages['webp'] }}" type="image/webp">
        <img
            src="{{ $mobileImages['fallback'] }}"
            class="h-full w-full object-cover md:object-[40%_center] xl:object-center"
            alt=""
            width="585"
            height="900"
            fetchpriority="high"
            loading="eager"
            decoding="async"
        >
    </picture>

    <div class="container relative z-10 h-full">
        <div class="mx-auto flex h-full max-w-sm flex-col items-center pt-16 text-center md:mx-0 md:max-w-md md:items-start md:pl-8 md:pt-24 md:text-left lg:max-w-lg lg:pt-28 xl:max-w-2xl xl:pl-3 xl:pt-32">
            <h1
                id="{{ $heroId }}-title"
                class="text-3xl font-extrabold leading-none text-heading md:text-5xl lg:text-6xl xl:text-[86px]"
            >
                {{ $heroTitle }}
            </h1>

            <p class="mt-3 text-base font-normal leading-snug text-heading md:mt-4 md:max-w-sm md:font-semibold md:leading-tight xl:max-w-2xl">
                {{ $heroDescription }}
            </p>

            <x-button-blue
                type="button"
                @click="showCallbackModal(null, 'otpravka-formy')"
                class="relative z-10 mb-8 mt-auto w-full !rounded-lg !px-8 !py-4 text-base leading-tight md:mb-0 md:mt-5 md:w-auto xl:mt-6"
            >
                {{ $heroButtonText }}
            </x-button-blue>
        </div>
    </div>

    <span class="sr-only">
        Имеются противопоказания. Необходима консультация специалиста.
    </span>
</div>
