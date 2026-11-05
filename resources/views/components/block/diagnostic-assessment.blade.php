<div class="mx-auto grid w-full container items-center gap-3 px-4  lg:grid-cols-2 lg:gap-10">
    <div class="text-heading">
        @unless ($block->title_hidden)
            <h2 class="mb-4 text-balance text-center text-[28px] font-semibold leading-[1.2] lg:text-wrap lg:text-left lg:text-[34px]">
                {{ $assessmentTitle }}
            </h2>
        @endunless

        <ol role="list" class="grid gap-4 text-base leading-[1.3]">
            @foreach ($assessmentItems as $item)
                <li class="flex min-h-8 items-center gap-4">
                    <span aria-hidden="true" class="grid size-8 shrink-0 place-items-center rounded-full bg-action-primary text-lg font-bold leading-none text-white">
                        {{ $loop->iteration }}
                    </span>
                    <span class="min-w-0 break-words">{{ $item }}</span>
                </li>
            @endforeach
        </ol>
    </div>

    <div class="relative mx-auto flex  w-full max-w-[570px] flex-col gap-6 overflow-hidden rounded-20 border border-action-primary bg-[#ffead7] pt-7">
        <p class="break-words px-6 text-base leading-[1.3] text-black">{{ $assessmentDescription }}</p>

        <div class="relative mt-auto">
            <picture class="block">
                <source media="(min-width: 640px)" type="image/webp" srcset="{{ asset('images/diagnostic-assessment/desktop.webp') }}" width="570" height="305">
                <img src="{{ asset('images/diagnostic-assessment/mobile.webp') }}" width="358" height="221" class="block h-auto w-full" alt="" loading="lazy" decoding="async">
            </picture>

            <x-button-primary
                type="button"
                @click="openBookingWidgetV3('otpravka-formy')"
                class="hidden lg:block absolute inset-x-0 bottom-4 mx-auto w-fit max-w-[calc(100%-2rem)]"
            >
                Записаться на приём
            </x-button-primary>
        </div>
    </div>
</div>
