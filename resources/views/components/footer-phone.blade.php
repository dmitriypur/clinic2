<div class="flex gap-2">
    <div class="w-4 h-4 pt-0.5">
                                    <span
                                        class="inline-flex w-4 h-4 text-icon-interactive">
                                        <x-icon-phone></x-icon-phone>
                                    </span>
    </div>
    <div class="font-medium flex flex-col items-end">
        <a href="tel:{{ $phone }}"
           class="text-sm/3 md:text-lg/6">{{ $phone }}</a>
        <button
            class="text-xs md:text-sm underline decoration-dotted text-interactive hover:no-underline"
            @click="showCallbackModal(null, 'otpravka-formy')">
            Перезвоните мне
        </button>
    </div>
</div>
