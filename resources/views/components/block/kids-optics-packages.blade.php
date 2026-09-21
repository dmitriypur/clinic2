<div class="container">
    <h2 class="mb-4 text-center text-[28px] font-semibold leading-[1.2] text-heading md:mb-8 md:text-[34px]">
        {{ $packagesTitle }}
    </h2>

    <div class="grid grid-cols-1 justify-items-center gap-6 xl:grid-cols-2 xl:gap-5">
        <article
            class="relative min-h-[614px] w-full max-w-[616px] overflow-hidden rounded-[24px] border border-[#808eae]/30 bg-white text-heading md:h-[660px] md:min-h-0"
            data-kids-optics-package="basic"
        >
            <span aria-hidden="true" class="absolute -bottom-[323px] -right-[392px] size-[712px] rounded-full bg-[#f8f8f8]"></span>

            <div
                style="--kids-optics-check-icon: url('{{ url('/images/kids-optics/packages/check-basic.svg') }}');"
                class="absolute inset-0 z-10 px-[15px] pt-4 text-base leading-[1.3]
                    [&_h3]:text-[34px] [&_h3]:font-semibold [&_h3]:leading-[1.2]
                    [&_h3+p]:mt-2 md:[&_h3+p]:mt-4 md:px-[29px] md:pt-[29px]
                    [&_h3+p]:max-w-[410px] [&_h3+p]:font-normal
                    [&_ul]:mt-3 [&_ul]:space-y-1.5 md:[&_ul]:mt-4
                    [&_li]:relative [&_li]:flex [&_li]:min-h-6 [&_li]:items-center [&_li]:pl-[31px] [&_li]:font-bold
                    [&_li]:before:absolute [&_li]:before:left-0 [&_li]:before:top-1/2 [&_li]:before:size-6 [&_li]:before:-translate-y-1/2 [&_li]:before:content-['']
                    [&_li]:before:[background-image:var(--kids-optics-check-icon)] [&_li]:before:bg-contain [&_li]:before:bg-center [&_li]:before:bg-no-repeat
                    md:[&_li]:min-h-10 md:[&_li]:pl-[50px] md:[&_li]:before:size-10
                    [&_.kids-optics-package-price]:absolute [&_.kids-optics-package-price]:bottom-[88px] [&_.kids-optics-package-price]:left-1/2 [&_.kids-optics-package-price]:m-0 [&_.kids-optics-package-price]:-translate-x-1/2 [&_.kids-optics-package-price]:whitespace-nowrap [&_.kids-optics-package-price]:text-[64px] [&_.kids-optics-package-price]:font-semibold [&_.kids-optics-package-price]:leading-none
                    md:[&_.kids-optics-package-price]:bottom-[105px] md:[&_.kids-optics-package-price]:left-[29px] md:[&_.kids-optics-package-price]:translate-x-0"
            >
                {!! $basicCardHtml !!}
            </div>

            <x-block.partials.kids-optics-package-parts variant="basic" class="top-[293px] md:top-[362px]" />

            <picture class="pointer-events-none absolute bottom-0 right-2 z-10 hidden w-[249px] md:block" aria-hidden="true">
                <source srcset="{{ $basicCharacter['avif'] }}" type="image/avif">
                <source srcset="{{ $basicCharacter['webp'] }}" type="image/webp">
                <img src="{{ $basicCharacter['fallback'] }}" alt="" width="374" height="450" loading="lazy" decoding="async">
            </picture>

            <x-button-blue
                type="button"
                @click="showCallbackModal(null, 'otpravka-formy')"
                class="!absolute bottom-[15px] left-[15px] right-[15px] z-20 !rounded-[10px] !px-[30px] !py-4 text-base leading-[1.22] md:bottom-[29px] md:left-[29px] md:right-auto"
            >
                {{ $buttonText }}
            </x-button-blue>
        </article>

        <article
            class="relative min-h-[737px] w-full max-w-[614px] overflow-hidden rounded-[24px] bg-[#f77c27] text-white shadow-[0_14px_24px_rgba(141,104,104,0.12)] md:h-[660px] md:min-h-0"
            data-kids-optics-package="premium"
        >
            <span aria-hidden="true" class="absolute -bottom-[303px] -right-[353px] size-[794px] rounded-full bg-[#ffa463]"></span>

            <div
                style="--kids-optics-check-icon: url('{{ url('/images/kids-optics/packages/check-premium.svg') }}'); --kids-optics-discount-icon: url('{{ url('/images/kids-optics/packages/discount.svg') }}');"
                class="absolute inset-0 z-10 px-4 pt-[76px] text-base leading-[1.3]
                    [&_h3]:text-[34px] [&_h3]:font-semibold [&_h3]:leading-[1.2]
                    [&_h3+p]:mt-2 md:[&_h3+p]:mt-4 md:px-[30px] md:pt-[30px]
                    [&_h3+p]:max-w-[326px] [&_h3+p]:font-normal
                    [&_ul]:mt-3 [&_ul]:space-y-1.5 md:[&_ul]:mt-4
                    [&_li]:relative [&_li]:flex [&_li]:min-h-6 [&_li]:items-center [&_li]:pl-[34px] [&_li]:font-bold
                    [&_li]:before:absolute [&_li]:before:left-0 [&_li]:before:top-1/2 [&_li]:before:size-6 [&_li]:before:-translate-y-1/2 [&_li]:before:content-['']
                    [&_li]:before:[background-image:var(--kids-optics-check-icon)] [&_li]:before:bg-contain [&_li]:before:bg-center [&_li]:before:bg-no-repeat
                    md:[&_li]:min-h-10 md:[&_li]:pl-[50px] md:[&_li]:before:size-10
                    [&_.kids-optics-package-saving]:absolute [&_.kids-optics-package-saving]:left-4 [&_.kids-optics-package-saving]:top-4 [&_.kids-optics-package-saving]:m-0 [&_.kids-optics-package-saving]:inline-flex [&_.kids-optics-package-saving]:items-center [&_.kids-optics-package-saving]:gap-2.5 [&_.kids-optics-package-saving]:rounded-full [&_.kids-optics-package-saving]:bg-[#ee6534] [&_.kids-optics-package-saving]:py-3 [&_.kids-optics-package-saving]:pl-3.5 [&_.kids-optics-package-saving]:pr-5 [&_.kids-optics-package-saving]:font-semibold [&_.kids-optics-package-saving]:leading-[1.2]
                    [&_.kids-optics-package-saving]:before:size-6 [&_.kids-optics-package-saving]:before:shrink-0 [&_.kids-optics-package-saving]:before:content-[''] [&_.kids-optics-package-saving]:before:[background-image:var(--kids-optics-discount-icon)] [&_.kids-optics-package-saving]:before:bg-contain [&_.kids-optics-package-saving]:before:bg-center [&_.kids-optics-package-saving]:before:bg-no-repeat
                    md:[&_.kids-optics-package-saving]:left-auto md:[&_.kids-optics-package-saving]:right-[22px] md:[&_.kids-optics-package-saving]:top-[30px] md:[&_.kids-optics-package-saving]:before:size-10
                    [&_.kids-optics-package-price]:absolute [&_.kids-optics-package-price]:bottom-[88px] [&_.kids-optics-package-price]:left-1/2 [&_.kids-optics-package-price]:m-0 [&_.kids-optics-package-price]:-translate-x-1/2 [&_.kids-optics-package-price]:whitespace-nowrap [&_.kids-optics-package-price]:text-[64px] [&_.kids-optics-package-price]:font-semibold [&_.kids-optics-package-price]:leading-none
                    md:[&_.kids-optics-package-price]:bottom-[105px] md:[&_.kids-optics-package-price]:left-[30px] md:[&_.kids-optics-package-price]:translate-x-0 md:pt-[30px]"
            >
                {!! $premiumCardHtml !!}
            </div>

            <x-block.partials.kids-optics-package-parts variant="premium" class="top-[417px] md:top-[409px]" />

            <picture class="pointer-events-none absolute bottom-0 right-0 z-10 hidden w-[274px] md:block" aria-hidden="true">
                <source srcset="{{ $premiumCharacter['avif'] }}" type="image/avif">
                <source srcset="{{ $premiumCharacter['webp'] }}" type="image/webp">
                <img src="{{ $premiumCharacter['fallback'] }}" alt="" width="387" height="471" loading="lazy" decoding="async">
            </picture>

            <x-button-blue
                type="button"
                @click="showCallbackModal(null, 'otpravka-formy')"
                class="!absolute bottom-4 left-4 right-4 z-20 !rounded-[10px] !px-[30px] !py-4 text-base leading-[1.22] md:bottom-[30px] md:left-[30px] md:right-auto"
            >
                {{ $buttonText }}
            </x-button-blue>
        </article>
    </div>
</div>
