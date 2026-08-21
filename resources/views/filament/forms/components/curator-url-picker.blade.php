@include('filament.forms.components.replaceable-curator-picker')

@php
    $selectedMedia = collect($getState() ?? [])->first();
    $mediaUrl = is_array($selectedMedia) ? ($selectedMedia['url'] ?? null) : null;
@endphp

@if($mediaUrl)
    <div
        class="mt-3 flex items-center gap-2"
        x-data="{
            copied: false,
            async copyUrl() {
                const value = this.$refs.mediaUrl.value;

                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(value);
                } else {
                    this.$refs.mediaUrl.select();
                    document.execCommand('copy');
                }

                this.copied = true;
                setTimeout(() => this.copied = false, 1500);
            },
        }"
    >
        <input
            x-ref="mediaUrl"
            type="text"
            readonly
            value="{{ $mediaUrl }}"
            class="block min-w-0 flex-1 rounded-lg border-gray-300 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5"
        >
        <button
            type="button"
            class="fi-btn fi-btn-size-md rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-200"
            x-on:click="copyUrl()"
            x-text="copied ? 'Скопировано' : 'Скопировать URL'"
        ></button>
    </div>
@endif
