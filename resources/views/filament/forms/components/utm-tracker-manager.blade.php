@php
    $hasInlineLabel = $hasInlineLabel();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();
    $trackingBaseUrl = rtrim((string) ($trackingBaseUrl ?? config('app.url')), '/');
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :has-inline-label="$hasInlineLabel"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            activeTab: 'tracking',
            isSyncing: false,
            trackingBaseUrl: @js($trackingBaseUrl),
            copiedTrackingKey: null,
            copyResetTimer: null,
            init() {
                this.ensureState()

                this.$watch('state', () => {
                    if (this.isSyncing) {
                        return
                    }

                    this.ensureState()
                })
            },
            emptyState() {
                return {
                    phones: [],
                    sources: [],
                    mediums: [],
                }
            },
            makeKey(prefix) {
                return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
            },
            ensureState() {
                const state = (this.state && typeof this.state === 'object') ? this.state : this.emptyState()

                state.phones = this.normalizePhones(state.phones)
                state.sources = this.normalizeSources(state.sources)
                state.mediums = this.normalizeMediums(state.mediums)

                this.writeState(state)
            },
            normalizePhones(rows) {
                return (Array.isArray(rows) ? rows : []).map((row) => ({
                    key: row?.key ?? this.makeKey('phone'),
                    id: row?.id ?? null,
                    phone: row?.phone ?? '',
                }))
            },
            normalizeSources(rows) {
                return (Array.isArray(rows) ? rows : []).map((row) => ({
                    key: row?.key ?? this.makeKey('source'),
                    id: row?.id ?? null,
                    source: row?.source ?? '',
                    name: row?.name ?? '',
                    default_phone_key: row?.default_phone_key ?? '',
                    open_booking_widget: !! row?.open_booking_widget,
                }))
            },
            normalizeMediums(rows) {
                return (Array.isArray(rows) ? rows : []).map((row) => ({
                    key: row?.key ?? this.makeKey('medium'),
                    id: row?.id ?? null,
                    source_key: row?.source_key ?? '',
                    medium: row?.medium ?? '',
                    medium_name: row?.medium_name ?? '',
                    phone_key: row?.phone_key ?? '',
                    open_booking_widget: !! row?.open_booking_widget,
                    start_date: row?.start_date ?? '',
                    end_date: row?.end_date ?? '',
                }))
            },
            writeState(state) {
                this.isSyncing = true
                this.state = {
                    phones: state.phones,
                    sources: state.sources,
                    mediums: state.mediums,
                }

                this.$nextTick(() => {
                    this.isSyncing = false
                })
            },
            syncState() {
                this.writeState({
                    phones: this.normalizePhones(this.state.phones),
                    sources: this.normalizeSources(this.state.sources),
                    mediums: this.normalizeMediums(this.state.mediums),
                })
            },
            currentDateValue() {
                const date = new Date()
                const month = `${date.getMonth() + 1}`.padStart(2, '0')
                const day = `${date.getDate()}`.padStart(2, '0')

                return `${date.getFullYear()}-${month}-${day}`
            },
            addPhone() {
                this.state.phones.unshift({
                    key: this.makeKey('phone'),
                    id: null,
                    phone: '',
                })

                this.activeTab = 'phones'
                this.syncState()
            },
            deletePhone(phoneKey) {
                if (this.isPhoneBusy(phoneKey)) {
                    return
                }

                this.state.phones = this.state.phones.filter((row) => row.key !== phoneKey)
                this.syncState()
            },
            addSource() {
                this.state.sources.unshift({
                    key: this.makeKey('source'),
                    id: null,
                    source: '',
                    name: '',
                    default_phone_key: '',
                    open_booking_widget: false,
                })

                this.activeTab = 'sources'
                this.syncState()
            },
            deleteSource(sourceKey) {
                this.state.sources = this.state.sources.filter((row) => row.key !== sourceKey)
                this.state.mediums = this.state.mediums.filter((row) => row.source_key !== sourceKey)
                this.syncState()
            },
            addMedium() {
                this.state.mediums.unshift({
                    key: this.makeKey('medium'),
                    id: null,
                    source_key: this.state.sources[0]?.key ?? '',
                    medium: '',
                    medium_name: '',
                    phone_key: '',
                    open_booking_widget: false,
                    start_date: '',
                    end_date: '',
                })

                this.activeTab = 'tracking'
                this.syncState()
            },
            deleteMedium(mediumKey) {
                this.state.mediums = this.state.mediums.filter((row) => row.key !== mediumKey)
                this.syncState()
            },
            stopMedium(row) {
                const today = this.currentDateValue()

                if (row.start_date && row.start_date > today) {
                    row.start_date = today
                }

                row.end_date = today
                this.syncState()
            },
            resumeMedium(row) {
                row.end_date = ''

                if (! row.start_date) {
                    row.start_date = this.currentDateValue()
                }

                this.syncState()
            },
            isMediumStopped(row) {
                return !! row.end_date && row.end_date <= this.currentDateValue()
            },
            isMediumScheduled(row) {
                return !! row.start_date && row.start_date > this.currentDateValue() && ! this.isMediumStopped(row)
            },
            isMediumActive(row) {
                return ! this.isMediumStopped(row) && ! this.isMediumScheduled(row)
            },
            mediumStatusLabel(row) {
                if (this.isMediumStopped(row)) {
                    return 'остановлена'
                }

                if (this.isMediumScheduled(row)) {
                    return 'запланирована'
                }

                return 'активна'
            },
            mediumStatusClass(row) {
                if (this.isMediumStopped(row)) {
                    return 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-200'
                }

                if (this.isMediumScheduled(row)) {
                    return 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200'
                }

                return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200'
            },
            sourceOptions() {
                return this.state.sources
            },
            isCurrentPhoneSelection(phoneKey, context = {}) {
                if (context.type === 'source') {
                    const row = this.state.sources.find((item) => item.key === context.key)

                    return row?.default_phone_key === phoneKey
                }

                if (context.type === 'medium') {
                    const row = this.state.mediums.find((item) => item.key === context.key)

                    return row?.phone_key === phoneKey
                }

                return false
            },
            sourceLabel(sourceKey) {
                const source = this.state.sources.find((row) => row.key === sourceKey)

                if (! source) {
                    return '—'
                }

                return source.name ? `${source.source} (${source.name})` : source.source
            },
            isPhoneBusy(phoneKey, context = {}) {
                const sourceUse = this.state.sources.some((row) => row.default_phone_key === phoneKey && ! (context.type === 'source' && context.key === row.key))
                const mediumUse = this.state.mediums.some((row) => row.phone_key === phoneKey && ! (context.type === 'medium' && context.key === row.key))

                return sourceUse || mediumUse
            },
            isPhoneOptionDisabled(phoneKey, context = {}) {
                if (this.isCurrentPhoneSelection(phoneKey, context)) {
                    return false
                }

                return this.isPhoneBusy(phoneKey, context)
            },
            phoneOptionLabel(phoneKey, context = {}) {
                const phone = this.phoneLabel(phoneKey)

                return this.isPhoneOptionDisabled(phoneKey, context) ? `${phone} (занят)` : phone
            },
            isPhoneBusyForDisplay(phoneKey) {
                return this.state.sources.some((row) => row.default_phone_key === phoneKey) || this.state.mediums.some((row) => row.phone_key === phoneKey)
            },
            phoneUsage(phoneKey) {
                const source = this.state.sources.find((row) => row.default_phone_key === phoneKey)

                if (source) {
                    return source.source ? `Source: ${source.source}` : 'Источник'
                }

                const medium = this.state.mediums.find((row) => row.phone_key === phoneKey)

                if (! medium) {
                    return 'Свободен'
                }

                const sourceLabel = this.sourceLabel(medium.source_key)

                return medium.medium ? `${sourceLabel} / ${medium.medium}` : sourceLabel
            },
            phoneLabel(phoneKey) {
                return this.state.phones.find((row) => row.key === phoneKey)?.phone ?? '—'
            },
            trackingRows() {
                const rows = []
                const sourceKeys = new Set(this.state.sources.map((sourceRow) => sourceRow.key))

                this.state.sources.forEach((sourceRow) => {
                    rows.push({
                        key: `tracking-source-${sourceRow.key}`,
                        type: 'source',
                        sourceRow,
                        mediumRow: null,
                    })

                    this.state.mediums
                        .filter((mediumRow) => mediumRow.source_key === sourceRow.key)
                        .forEach((mediumRow) => {
                            rows.push({
                                key: `tracking-medium-${mediumRow.key}`,
                                type: 'medium',
                                sourceRow,
                                mediumRow,
                            })
                        })
                })

                this.state.mediums
                    .filter((mediumRow) => ! sourceKeys.has(mediumRow.source_key))
                    .forEach((mediumRow) => {
                        rows.push({
                            key: `tracking-medium-${mediumRow.key}`,
                            type: 'medium',
                            sourceRow: null,
                            mediumRow,
                        })
                    })

                return rows
            },
            buildUrl(sourceValue = '', mediumValue = '', shouldOpenWidget = false) {
                const source = String(sourceValue || '').trim()
                const medium = String(mediumValue || '').trim()

                if (! source) {
                    return this.trackingBaseUrl
                }

                const url = new URL(this.trackingBaseUrl)

                url.searchParams.set('utm_source', source)

                if (medium) {
                    url.searchParams.set('utm_medium', medium)
                }

                if (shouldOpenWidget) {
                    url.hash = 'appointment-form'
                }

                return url.toString()
            },
            trackingLinkValue(trackingRow) {
                if (trackingRow.type === 'source') {
                    return this.buildUrl(
                        trackingRow.sourceRow?.source,
                        '',
                        trackingRow.sourceRow?.open_booking_widget,
                    )
                }

                return this.buildUrl(
                    trackingRow.sourceRow?.source,
                    trackingRow.mediumRow?.medium,
                    trackingRow.mediumRow?.open_booking_widget,
                )
            },
            async copyText(value) {
                const text = String(value ?? '').trim()

                if (! text) {
                    return false
                }

                try {
                    if (navigator?.clipboard?.writeText) {
                        await navigator.clipboard.writeText(text)

                        return true
                    }
                } catch (error) {
                    // Fallback below.
                }

                const textarea = document.createElement('textarea')
                textarea.value = text
                textarea.setAttribute('readonly', '')
                textarea.style.position = 'absolute'
                textarea.style.left = '-9999px'
                document.body.appendChild(textarea)
                textarea.select()

                let copied = false

                try {
                    copied = document.execCommand('copy')
                } catch (error) {
                    copied = false
                }

                document.body.removeChild(textarea)

                return copied
            },
            async copyTrackingLink(trackingRow) {
                const copied = await this.copyText(this.trackingLinkValue(trackingRow))

                if (! copied) {
                    return
                }

                this.copiedTrackingKey = trackingRow.key

                if (this.copyResetTimer) {
                    clearTimeout(this.copyResetTimer)
                }

                this.copyResetTimer = setTimeout(() => {
                    if (this.copiedTrackingKey === trackingRow.key) {
                        this.copiedTrackingKey = null
                    }
                }, 1500)
            },
            isTrackingLinkCopied(trackingRow) {
                return this.copiedTrackingKey === trackingRow.key
            },
        }"
        {{
            $attributes
                ->merge($getExtraAlpineAttributes(), escape: false)
                ->class(['w-full'])
        }}
        wire:ignore
        wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}"
    >
        <div class="w-full rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                        x-bind:class="activeTab === 'tracking' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-200'"
                        x-on:click="activeTab = 'tracking'"
                    >
                        Основной
                    </button>

                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                        x-bind:class="activeTab === 'sources' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-200'"
                        x-on:click="activeTab = 'sources'"
                    >
                        Источники
                    </button>

                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                        x-bind:class="activeTab === 'phones' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-200'"
                        x-on:click="activeTab = 'phones'"
                    >
                        Телефоны
                    </button>
                </div>
            </div>

            <div class="p-4">
                <div x-show="activeTab === 'tracking'" x-cloak class="space-y-3">
                    <div class="flex justify-end">
                        <button
                            type="button"
                            class="text-sm text-primary-600 hover:underline dark:text-primary-400"
                            x-on:click="addMedium()"
                            @disabled($isDisabled)
                        >
                            + добавить medium
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-white/10">
                                    <th class="w-[14%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Source</th>
                                    <th class="w-[9%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Medium</th>
                                    <th class="w-[6%] py-2 pr-3 text-center font-medium text-gray-500 dark:text-gray-400">Виджет</th>
                                    <th class="w-[18%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Ссылка</th>
                                    <th class="w-[10%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Название</th>
                                    <th class="w-[10%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Телефон</th>
                                    <th class="w-[8%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Начало</th>
                                    <th class="w-[8%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Конец</th>
                                    <th class="w-[4%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Статус</th>
                                    <th class="py-2 text-right font-medium text-gray-500 dark:text-gray-400">Действия</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="trackingRows().length === 0">
                                    <tr>
                                        <td colspan="10" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Пока нет ни одной UTM-ссылки.
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="trackingRow in trackingRows()" :key="trackingRow.key">
                                    <tr
                                        class="border-b border-gray-200 dark:border-white/10"
                                        x-bind:class="trackingRow.type === 'source' ? 'bg-gray-50/70 dark:bg-white/[0.02]' : ''"
                                    >
                                        <td class="py-2 pr-3 align-top">
                                            <template x-if="trackingRow.type === 'source'">
                                                <div class="rounded-md border border-transparent px-2 py-1 text-sm text-gray-900 dark:text-white" x-text="trackingRow.sourceRow?.source || '—'"></div>
                                            </template>

                                            <template x-if="trackingRow.type === 'medium'">
                                                <select
                                                    class="block w-full rounded-md border-gray-300 px-2 py-1 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                                    x-model="trackingRow.mediumRow.source_key"
                                                    x-on:change="syncState()"
                                                    @disabled($isDisabled)
                                                >
                                                    <option value="">Выберите source</option>
                                                    <template x-for="sourceRow in sourceOptions()" :key="sourceRow.key">
                                                        <option
                                                            x-bind:selected="trackingRow.mediumRow.source_key === sourceRow.key"
                                                            x-bind:value="sourceRow.key"
                                                            x-text="sourceRow.name ? `${sourceRow.source} (${sourceRow.name})` : sourceRow.source"
                                                        ></option>
                                                    </template>
                                                </select>
                                            </template>
                                        </td>

                                        <td class="py-2 pr-3 align-top">
                                            <template x-if="trackingRow.type === 'source'">
                                                <div class="rounded-md border border-transparent px-2 py-1 text-sm text-gray-400 dark:text-gray-500">—</div>
                                            </template>

                                            <template x-if="trackingRow.type === 'medium'">
                                                <input
                                                    type="text"
                                                    class="block w-full rounded-md border-gray-300 px-2 py-1 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                                    x-model="trackingRow.mediumRow.medium"
                                                    x-on:input.debounce.300ms="syncState()"
                                                    placeholder="cpc"
                                                    @disabled($isDisabled)
                                                />
                                            </template>
                                        </td>

                                        <td class="py-2 pr-3 align-top text-center">
                                            <template x-if="trackingRow.type === 'source'">
                                                <label class="inline-flex h-8 items-center justify-center">
                                                    <input
                                                        type="checkbox"
                                                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent"
                                                        x-model="trackingRow.sourceRow.open_booking_widget"
                                                        x-on:change="syncState()"
                                                        @disabled($isDisabled)
                                                    />
                                                </label>
                                            </template>

                                            <template x-if="trackingRow.type === 'medium'">
                                                <label class="inline-flex h-8 items-center justify-center">
                                                    <input
                                                        type="checkbox"
                                                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent"
                                                        x-model="trackingRow.mediumRow.open_booking_widget"
                                                        x-on:change="syncState()"
                                                        @disabled($isDisabled)
                                                    />
                                                </label>
                                            </template>
                                        </td>

                                        <td class="py-2 pr-3 align-top">
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    readonly
                                                    class="block w-full rounded-md border-gray-300 bg-gray-50 px-2 py-1 text-xs shadow-none dark:border-white/10 dark:bg-white/5 dark:text-white"
                                                    x-bind:value="trackingLinkValue(trackingRow)"
                                                />

                                                <button
                                                    type="button"
                                                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border transition"
                                                    x-bind:class="isTrackingLinkCopied(trackingRow) ? 'border-emerald-200 text-emerald-600 hover:bg-emerald-50 dark:border-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/10' : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5'"
                                                    x-bind:title="isTrackingLinkCopied(trackingRow) ? 'Скопировано' : 'Скопировать ссылку'"
                                                    x-bind:aria-label="isTrackingLinkCopied(trackingRow) ? 'Скопировано' : 'Скопировать ссылку'"
                                                    x-on:click="copyTrackingLink(trackingRow)"
                                                    @disabled($isDisabled)
                                                >
                                                    <svg
                                                        x-show="! isTrackingLinkCopied(trackingRow)"
                                                        class="h-4 w-4"
                                                        viewBox="0 0 20 20"
                                                        fill="#374151"
                                                        aria-hidden="true"
                                                    >
                                                        <path d="M6.75 2A2.75 2.75 0 0 0 4 4.75v6.5A2.75 2.75 0 0 0 6.75 14h6.5A2.75 2.75 0 0 0 16 11.25v-6.5A2.75 2.75 0 0 0 13.25 2h-6.5Zm-1.25 2.75c0-.69.56-1.25 1.25-1.25h6.5c.69 0 1.25.56 1.25 1.25v6.5c0 .69-.56 1.25-1.25 1.25h-6.5c-.69 0-1.25-.56-1.25-1.25v-6.5Z" />
                                                        <path d="M3.75 6.5a.75.75 0 0 1 .75.75v8a1.75 1.75 0 0 0 1.75 1.75h8a.75.75 0 0 1 0 1.5h-8A3.25 3.25 0 0 1 3 15.25v-8a.75.75 0 0 1 .75-.75Z" />
                                                    </svg>

                                                    <svg
                                                        x-show="isTrackingLinkCopied(trackingRow)"
                                                        class="h-4 w-4"
                                                        viewBox="0 0 20 20"
                                                        fill="#16a34a"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>

                                        <td class="py-2 pr-3 align-top">
                                            <template x-if="trackingRow.type === 'source'">
                                                <div class="rounded-md border border-transparent px-2 py-1 text-sm text-gray-900 dark:text-white" x-text="trackingRow.sourceRow?.name || '—'"></div>
                                            </template>

                                            <template x-if="trackingRow.type === 'medium'">
                                                <input
                                                    type="text"
                                                    class="block w-full rounded-md border-gray-300 px-2 py-1 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                                    x-model="trackingRow.mediumRow.medium_name"
                                                    x-on:input.debounce.300ms="syncState()"
                                                    placeholder="Google CPC"
                                                    @disabled($isDisabled)
                                                />
                                            </template>
                                        </td>

                                        <td class="py-2 pr-3 align-top">
                                            <template x-if="trackingRow.type === 'source'">
                                                <div class="rounded-md border border-transparent px-2 py-1 text-sm text-gray-900 dark:text-white" x-text="phoneLabel(trackingRow.sourceRow?.default_phone_key)"></div>
                                            </template>

                                            <template x-if="trackingRow.type === 'medium'">
                                                <select
                                                    class="block w-full rounded-md border-gray-300 px-2 py-1 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                                    x-model="trackingRow.mediumRow.phone_key"
                                                    x-on:change="syncState()"
                                                    @disabled($isDisabled)
                                                >
                                                    <option value="">Выберите телефон</option>
                                                    <template x-for="phoneRow in state.phones" :key="phoneRow.key">
                                                        <option
                                                            x-bind:disabled="isPhoneOptionDisabled(phoneRow.key, { type: 'medium', key: trackingRow.mediumRow.key })"
                                                            x-bind:selected="trackingRow.mediumRow.phone_key === phoneRow.key"
                                                            x-bind:value="phoneRow.key"
                                                            x-text="phoneOptionLabel(phoneRow.key, { type: 'medium', key: trackingRow.mediumRow.key })"
                                                        ></option>
                                                    </template>
                                                </select>
                                            </template>
                                        </td>

                                        <td class="py-2 pr-3 align-top">
                                            <template x-if="trackingRow.type === 'source'">
                                                <div class="rounded-md border border-transparent px-2 py-1 text-sm text-gray-400 dark:text-gray-500">—</div>
                                            </template>

                                            <template x-if="trackingRow.type === 'medium'">
                                                <input
                                                    type="date"
                                                    class="block w-full rounded-md border-gray-300 px-2 py-1 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                                    x-model="trackingRow.mediumRow.start_date"
                                                    x-on:change="syncState()"
                                                    @disabled($isDisabled)
                                                />
                                            </template>
                                        </td>

                                        <td class="py-2 pr-3 align-top">
                                            <template x-if="trackingRow.type === 'source'">
                                                <div class="rounded-md border border-transparent px-2 py-1 text-sm text-gray-400 dark:text-gray-500">—</div>
                                            </template>

                                            <template x-if="trackingRow.type === 'medium'">
                                                <input
                                                    type="date"
                                                    class="block w-full rounded-md border-gray-300 px-2 py-1 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                                    x-model="trackingRow.mediumRow.end_date"
                                                    x-on:change="syncState()"
                                                    @disabled($isDisabled)
                                                />
                                            </template>
                                        </td>

                                        <td class="py-2 pr-3 align-top">
                                            <template x-if="trackingRow.type === 'source'">
                                                <div class="rounded-md border border-transparent px-2 py-1 text-sm text-gray-400 dark:text-gray-500">—</div>
                                            </template>

                                            <template x-if="trackingRow.type === 'medium'">
                                                <span
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full"
                                                    x-bind:class="mediumStatusClass(trackingRow.mediumRow)"
                                                    x-bind:title="mediumStatusLabel(trackingRow.mediumRow)"
                                                >
                                                    <svg
                                                        x-show="isMediumActive(trackingRow.mediumRow)"
                                                        class="h-4 w-4"
                                                        viewBox="0 0 20 20"
                                                        fill="#16a34a"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>

                                                    <svg
                                                        x-show="isMediumStopped(trackingRow.mediumRow)"
                                                        class="h-4 w-4"
                                                        viewBox="0 0 20 20"
                                                        fill="#dc2626"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>

                                                    <svg
                                                        x-show="isMediumScheduled(trackingRow.mediumRow)"
                                                        class="h-4 w-4"
                                                        viewBox="0 0 20 20"
                                                        fill="#d97706"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm1-12a1 1 0 1 0-2 0v4c0 .265.105.52.293.707l2.5 2.5a1 1 0 1 0 1.414-1.414L11 9.586V6Z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>

                                                    <span class="sr-only" x-text="mediumStatusLabel(trackingRow.mediumRow)"></span>
                                                </span>
                                            </template>
                                        </td>

                                        <td class="py-2 text-right align-top">
                                            <template x-if="trackingRow.type === 'source'">
                                                <div class="rounded-md border border-transparent px-2 py-1 text-sm text-gray-400 dark:text-gray-500">—</div>
                                            </template>

                                            <template x-if="trackingRow.type === 'medium'">
                                                <div class="flex justify-end gap-2 whitespace-nowrap">
                                                    <button
                                                        type="button"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                                                        x-show="! isMediumStopped(trackingRow.mediumRow)"
                                                        x-on:click="stopMedium(trackingRow.mediumRow)"
                                                        title="Остановить"
                                                        aria-label="Остановить"
                                                        @disabled($isDisabled)
                                                    >
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="#374151" aria-hidden="true">
                                                            <path d="M6 6.75A.75.75 0 0 1 6.75 6h6.5a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-.75.75h-6.5A.75.75 0 0 1 6 13.25v-6.5Z" />
                                                        </svg>
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-emerald-200 text-emerald-600 transition hover:bg-emerald-50 dark:border-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/10"
                                                        x-show="isMediumStopped(trackingRow.mediumRow)"
                                                        x-on:click="resumeMedium(trackingRow.mediumRow)"
                                                        title="Возобновить"
                                                        aria-label="Возобновить"
                                                        @disabled($isDisabled)
                                                    >
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true">
                                                            <path
                                                                fill-rule="evenodd"
                                                                d="M6.22 5.22a.75.75 0 0 1 1.06 0l6 4.25a.75.75 0 0 1 0 1.22l-6 4.25A.75.75 0 0 1 6 14.31V5.75a.75.75 0 0 1 .22-.53Z"
                                                                clip-rule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                                        x-on:click="deleteMedium(trackingRow.mediumRow.key)"
                                                        title="Удалить"
                                                        aria-label="Удалить"
                                                        @disabled($isDisabled)
                                                    >
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true">
                                                            <path
                                                                fill-rule="evenodd"
                                                                d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z"
                                                                clip-rule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div x-show="activeTab === 'sources'" x-cloak class="space-y-3">
                    <div class="flex justify-end">
                        <button
                            type="button"
                            class="text-sm text-primary-600 hover:underline dark:text-primary-400"
                            x-on:click="addSource()"
                            @disabled($isDisabled)
                        >
                            + добавить source
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-white/10">
                                    <th class="w-[24%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Source</th>
                                    <th class="w-[28%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Название</th>
                                    <th class="py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Дефолтный телефон</th>
                                    <th class="w-24 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Действия</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="state.sources.length === 0">
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Пока нет ни одного source.
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="sourceRow in state.sources" :key="sourceRow.key">
                                    <tr class="border-b border-gray-200 dark:border-white/10">
                                        <td class="py-2 pr-3 align-top">
                                            <input
                                                type="text"
                                                class="block w-full rounded-md border-gray-300 px-2 py-1 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                                x-model="sourceRow.source"
                                                x-on:input.debounce.300ms="syncState()"
                                                placeholder="google"
                                                @disabled($isDisabled)
                                            />
                                        </td>

                                        <td class="py-2 pr-3 align-top">
                                            <input
                                                type="text"
                                                class="block w-full rounded-md border-gray-300 px-2 py-1 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                                x-model="sourceRow.name"
                                                x-on:input.debounce.300ms="syncState()"
                                                placeholder="Google Ads"
                                                @disabled($isDisabled)
                                            />
                                        </td>

                                        <td class="py-2 pr-3 align-top">
                                            <select
                                                class="block w-full rounded-md border-gray-300 px-2 py-1 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                                x-model="sourceRow.default_phone_key"
                                                x-on:change="syncState()"
                                                @disabled($isDisabled)
                                            >
                                                <option value="">Без дефолтного телефона</option>
                                                <template x-for="phoneRow in state.phones" :key="phoneRow.key">
                                                    <option
                                                        x-bind:disabled="isPhoneOptionDisabled(phoneRow.key, { type: 'source', key: sourceRow.key })"
                                                        x-bind:selected="sourceRow.default_phone_key === phoneRow.key"
                                                        x-bind:value="phoneRow.key"
                                                        x-text="phoneOptionLabel(phoneRow.key, { type: 'source', key: sourceRow.key })"
                                                    ></option>
                                                </template>
                                            </select>
                                        </td>

                                        <td class="py-2 text-right align-top">
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                                x-on:click="deleteSource(sourceRow.key)"
                                                title="Удалить"
                                                aria-label="Удалить"
                                                @disabled($isDisabled)
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div x-show="activeTab === 'phones'" x-cloak class="space-y-3">
                    <div class="flex justify-end">
                        <button
                            type="button"
                            class="text-sm text-primary-600 hover:underline dark:text-primary-400"
                            x-on:click="addPhone()"
                            @disabled($isDisabled)
                        >
                            + добавить телефон
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-white/10">
                                    <th class="w-[42%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Телефон</th>
                                    <th class="w-[16%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Статус</th>
                                    <th class="py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Использование</th>
                                    <th class="w-24 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Действия</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="state.phones.length === 0">
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Пока нет ни одного телефона.
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="phoneRow in state.phones" :key="phoneRow.key">
                                    <tr class="border-b border-gray-200 dark:border-white/10">
                                        <td class="py-2 pr-3 align-top">
                                            <input
                                                type="text"
                                                class="block w-full rounded-md border-gray-300 px-2 py-1 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                                x-model="phoneRow.phone"
                                                x-on:input.debounce.300ms="syncState()"
                                                placeholder="+7 (999) 000-00-00"
                                                @disabled($isDisabled)
                                            />
                                        </td>

                                        <td class="py-2 pr-3 align-top">
                                            <span
                                                class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                                x-bind:class="isPhoneBusyForDisplay(phoneRow.key) ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200'"
                                                x-text="isPhoneBusyForDisplay(phoneRow.key) ? 'занят' : 'свободен'"
                                            ></span>
                                        </td>

                                        <td class="py-2 pr-3 align-top text-gray-600 dark:text-gray-300" x-text="phoneUsage(phoneRow.key)"></td>

                                        <td class="py-2 text-right align-top">
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border transition"
                                                x-bind:class="isPhoneBusyForDisplay(phoneRow.key) ? 'cursor-not-allowed border-gray-200 text-gray-400 dark:border-white/10 dark:text-gray-500' : 'border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10'"
                                                x-bind:style="isPhoneBusyForDisplay(phoneRow.key) ? 'border-color:#e5e7eb;' : 'border-color:#fecaca;'"
                                                x-on:click="deletePhone(phoneRow.key)"
                                                title="Удалить"
                                                aria-label="Удалить"
                                                @disabled($isDisabled)
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    viewBox="0 0 20 20"
                                                    x-bind:fill="isPhoneBusyForDisplay(phoneRow.key) ? '#9ca3af' : '#dc2626'"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
