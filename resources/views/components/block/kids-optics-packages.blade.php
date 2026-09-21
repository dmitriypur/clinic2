<div class="container">
    <h2 class="mb-4 text-center text-3xl font-semibold leading-tight text-heading md:mb-8 md:text-4xl">
        {{ $packagesTitle }}
    </h2>

    <div class="grid grid-cols-1 justify-items-center gap-6 xl:grid-cols-2 xl:gap-5">
        <article
            class="relative min-h-[614px] w-full max-w-[616px] overflow-hidden rounded-3xl border border-[#808eae]/30 bg-white text-heading md:h-[660px] md:min-h-0"
            data-kids-optics-package="basic"
        >
            <span aria-hidden="true" class="absolute -bottom-[323px] -right-[392px] size-[712px] rounded-full bg-[#f8f8f8]"></span>

            <div
                style="--kids-optics-check-icon: url('{{ url('/images/kids-optics/packages/check-basic.svg') }}');"
                class="absolute inset-0 z-10 px-4 pt-4 text-base leading-snug
                    [&_h3]:text-4xl [&_h3]:font-semibold [&_h3]:leading-tight
                    [&_h3+p]:mt-2 md:[&_h3+p]:mt-4 md:px-7 md:pt-7
                    [&_h3+p]:max-w-md [&_h3+p]:font-normal
                    [&_ul]:mt-3 [&_ul]:space-y-1.5 md:[&_ul]:mt-4
                    [&_li]:relative [&_li]:flex [&_li]:min-h-6 [&_li]:items-center [&_li]:pl-8 [&_li]:font-bold
                    [&_li]:before:absolute [&_li]:before:left-0 [&_li]:before:top-1/2 [&_li]:before:size-6 [&_li]:before:-translate-y-1/2 [&_li]:before:content-['']
                    [&_li]:before:[background-image:var(--kids-optics-check-icon)] [&_li]:before:bg-contain [&_li]:before:bg-center [&_li]:before:bg-no-repeat
                    md:[&_li]:min-h-10 md:[&_li]:pl-12 md:[&_li]:before:size-10
                    [&_.kids-optics-package-price]:absolute [&_.kids-optics-package-price]:bottom-24 [&_.kids-optics-package-price]:left-1/2 [&_.kids-optics-package-price]:m-0 [&_.kids-optics-package-price]:-translate-x-1/2 [&_.kids-optics-package-price]:whitespace-nowrap [&_.kids-optics-package-price]:text-6xl [&_.kids-optics-package-price]:font-semibold [&_.kids-optics-package-price]:leading-none
                    md:[&_.kids-optics-package-price]:bottom-28 md:[&_.kids-optics-package-price]:left-7 md:[&_.kids-optics-package-price]:translate-x-0"
            >
                {!! $basicCardHtml !!}
            </div>

            <x-block.partials.kids-optics-package-parts variant="basic" class="top-[293px] md:top-[362px]" />

            <picture class="pointer-events-none absolute bottom-0 right-2 z-10 hidden w-64 md:block" aria-hidden="true">
                <source srcset="{{ $basicCharacter['avif'] }}" type="image/avif">
                <source srcset="{{ $basicCharacter['webp'] }}" type="image/webp">
                <img src="{{ $basicCharacter['fallback'] }}" alt="" width="374" height="450" loading="lazy" decoding="async">
            </picture>

            <x-button-blue
                type="button"
                @click="showCallbackModal(null, 'otpravka-formy')"
                class="!absolute bottom-4 left-4 right-4 z-20 !rounded-lg !px-8 !py-4 text-base leading-tight md:bottom-7 md:left-7 md:right-auto"
            >
                {{ $buttonText }}
            </x-button-blue>
        </article>

        <article
            class="relative min-h-[737px] w-full max-w-[614px] overflow-hidden rounded-3xl bg-[#f77c27] text-white shadow-[0_14px_24px_rgba(141,104,104,0.12)] md:h-[660px] md:min-h-0"
            data-kids-optics-package="premium"
        >
            <span aria-hidden="true" class="absolute -bottom-[303px] -right-[353px] size-[794px] rounded-full bg-[#ffa463]"></span>

            <div
                style="--kids-optics-check-icon: url('{{ url('/images/kids-optics/packages/check-premium.svg') }}'); --kids-optics-discount-icon: url('{{ url('/images/kids-optics/packages/discount.svg') }}');"
                class="absolute inset-0 z-10 px-4 pt-20 text-base leading-snug
                    [&_h3]:text-4xl [&_h3]:font-semibold [&_h3]:leading-tight
                    [&_h3+p]:mt-2 md:[&_h3+p]:mt-4 md:px-8 md:pt-8
                    [&_h3+p]:max-w-xs [&_h3+p]:font-normal
                    [&_ul]:mt-3 [&_ul]:space-y-1.5 md:[&_ul]:mt-4
                    [&_li]:relative [&_li]:flex [&_li]:min-h-6 [&_li]:items-center [&_li]:pl-8 [&_li]:font-bold
                    [&_li]:before:absolute [&_li]:before:left-0 [&_li]:before:top-1/2 [&_li]:before:size-6 [&_li]:before:-translate-y-1/2 [&_li]:before:content-['']
                    [&_li]:before:[background-image:var(--kids-optics-check-icon)] [&_li]:before:bg-contain [&_li]:before:bg-center [&_li]:before:bg-no-repeat
                    md:[&_li]:min-h-10 md:[&_li]:pl-12 md:[&_li]:before:size-10
                    [&_.kids-optics-package-saving]:absolute [&_.kids-optics-package-saving]:left-4 [&_.kids-optics-package-saving]:top-4 [&_.kids-optics-package-saving]:m-0 [&_.kids-optics-package-saving]:inline-flex [&_.kids-optics-package-saving]:items-center [&_.kids-optics-package-saving]:gap-2.5 [&_.kids-optics-package-saving]:rounded-full [&_.kids-optics-package-saving]:bg-[#ee6534] [&_.kids-optics-package-saving]:py-3 [&_.kids-optics-package-saving]:pl-3.5 [&_.kids-optics-package-saving]:pr-5 [&_.kids-optics-package-saving]:font-semibold [&_.kids-optics-package-saving]:leading-tight
                    [&_.kids-optics-package-saving]:before:size-6 [&_.kids-optics-package-saving]:before:shrink-0 [&_.kids-optics-package-saving]:before:content-[''] [&_.kids-optics-package-saving]:before:[background-image:var(--kids-optics-discount-icon)] [&_.kids-optics-package-saving]:before:bg-contain [&_.kids-optics-package-saving]:before:bg-center [&_.kids-optics-package-saving]:before:bg-no-repeat
                    md:[&_.kids-optics-package-saving]:left-auto md:[&_.kids-optics-package-saving]:right-6 md:[&_.kids-optics-package-saving]:top-8 md:[&_.kids-optics-package-saving]:before:size-10
                    [&_.kids-optics-package-price]:absolute [&_.kids-optics-package-price]:bottom-24 [&_.kids-optics-package-price]:left-1/2 [&_.kids-optics-package-price]:m-0 [&_.kids-optics-package-price]:-translate-x-1/2 [&_.kids-optics-package-price]:whitespace-nowrap [&_.kids-optics-package-price]:text-6xl [&_.kids-optics-package-price]:font-semibold [&_.kids-optics-package-price]:leading-none
                    md:[&_.kids-optics-package-price]:bottom-28 md:[&_.kids-optics-package-price]:left-8 md:[&_.kids-optics-package-price]:translate-x-0 md:pt-8"
            >
                {!! $premiumCardHtml !!}
            </div>

            <x-block.partials.kids-optics-package-parts variant="premium" class="top-[417px] md:top-[409px]" />

            <picture class="pointer-events-none absolute bottom-0 right-0 z-10 hidden w-72 md:block" aria-hidden="true">
                <source srcset="{{ $premiumCharacter['avif'] }}" type="image/avif">
                <source srcset="{{ $premiumCharacter['webp'] }}" type="image/webp">
                <img src="{{ $premiumCharacter['fallback'] }}" alt="" width="387" height="471" loading="lazy" decoding="async">
            </picture>

            <x-button-blue
                type="button"
                @click="showCallbackModal(null, 'otpravka-formy')"
                class="!absolute bottom-4 left-4 right-4 z-20 !rounded-lg !px-8 !py-4 text-base leading-tight md:bottom-8 md:left-8 md:right-auto"
            >
                {{ $buttonText }}
            </x-button-blue>
        </article>
    </div>
</div>
