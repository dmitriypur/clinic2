<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <x-filament::section heading="Готовая ссылка">
            @php($generatedUrl = $this->getGeneratedUrl())

            <div
                class="space-y-4"
                x-data="{
                    copied: false,
                    fallbackCopy(value) {
                        const textarea = document.createElement('textarea')
                        textarea.value = value
                        textarea.setAttribute('readonly', '')
                        textarea.style.position = 'fixed'
                        textarea.style.left = '-9999px'
                        textarea.style.top = '0'
                        document.body.appendChild(textarea)
                        textarea.focus()
                        textarea.select()
                        const copied = document.execCommand('copy')
                        document.body.removeChild(textarea)

                        return copied
                    },
                    async copy(value) {
                        if (! value) {
                            return
                        }

                        try {
                            if (navigator?.clipboard?.writeText) {
                                await navigator.clipboard.writeText(value)
                            } else {
                                this.fallbackCopy(value)
                            }

                            this.copied = true
                            setTimeout(() => this.copied = false, 1500)
                        } catch (error) {
                            this.copied = this.fallbackCopy(value)

                            if (this.copied) {
                                setTimeout(() => this.copied = false, 1500)
                            }
                        }
                    }
                }"
            >
                @if($generatedUrl)
                    <div class="flex flex-col gap-3 lg:flex-row" wire:key="booking-link-preview-{{ md5($generatedUrl) }}">
                        <input
                            x-ref="generatedUrl"
                            type="text"
                            readonly
                            value="{{ $generatedUrl }}"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        >

                        <div class="flex shrink-0 gap-2">
                            <x-filament::button
                                type="button"
                                icon="heroicon-m-clipboard"
                                x-on:click="copy($refs.generatedUrl.value)"
                            >
                                <span x-show="! copied">Копировать</span>
                                <span x-show="copied" x-cloak>Скопировано</span>
                            </x-filament::button>

                            <x-filament::button
                                tag="a"
                                href="{{ $generatedUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                color="gray"
                                icon="heroicon-m-arrow-top-right-on-square"
                            >
                                Открыть
                            </x-filament::button>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Выберите врача или филиал, чтобы сформировать ссылку.
                    </p>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
