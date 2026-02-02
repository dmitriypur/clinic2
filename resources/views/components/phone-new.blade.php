<div class="flex gap-2">
    <div class="w-5 h-5 pt-0.5">
        <a href="tel:{{ $phone }}"
            class="inline-flex w-5 h-5 text-icon-interactive">
            <x-icon-phone-new class="fill-action-primary lg:fill-current"></x-icon-phone-new>
        </a>
    </div>
    <div class="hidden lg:block font-medium flex flex-col items-end">
        <a href="tel:{{ $phone }}"
           class="text-lg/6 font-semibold">{{ $phone }}</a>
    </div>
    <button
            class="hidden lg:block accessibility:hidden text-base/6 font-semibold text-action-primary ml-4 border-b border-action-primary hover:border-transparent"
            @click="showCallbackModal(null, 'otpravka-formy')">
            Перезвоните мне
        </button>
</div>
