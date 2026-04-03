@php
    $trackingBaseUrl = rtrim((string) ($trackingBaseUrl ?? config('app.url')), '/');
@endphp

<div
    x-data="{
        state: $wire.entangle('state'),
        activeTab: 'tracking',
        isSyncing: false,
        isPersisting: false,
        trackingBaseUrl: @js($trackingBaseUrl),
        copiedTrackingKey: null,
        copyResetTimer: null,
        selectedTrackingKeys: [],
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
                campaigns: [],
                archived_campaigns: [],
            }
        },
        makeKey(prefix) {
            return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
        },
        ensureState() {
            const state = (this.state && typeof this.state === 'object') ? this.state : this.emptyState()

            state.phones = this.normalizePhones(state.phones)
            state.sources = this.normalizeSources(state.sources)
            state.campaigns = this.normalizeCampaigns(state.campaigns)
            state.archived_campaigns = this.normalizeCampaigns(state.archived_campaigns)
            state.campaigns = this.dropConflictingSourceCampaignPhones(
                this.syncSourceOnlyCampaignRows(state).campaigns
            )

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
        normalizeCampaigns(rows) {
            return (Array.isArray(rows) ? rows : []).map((row) => {
                const type = ['source', 'medium'].includes(row?.type)
                    ? row.type
                    : ((row?.medium ?? '') ? 'medium' : 'source')

                return {
                    key: row?.key ?? this.makeKey('campaign'),
                    id: row?.id ?? null,
                    type,
                    source_key: row?.source_key ?? '',
                    medium: type === 'medium' ? (row?.medium ?? '') : '',
                    medium_name: type === 'medium' ? (row?.medium_name ?? '') : '',
                    phone_key: row?.phone_key ?? '',
                    open_booking_widget: !! row?.open_booking_widget,
                    started_at: row?.started_at ?? '',
                    stopped_at: row?.stopped_at ?? '',
                    archived_at: row?.archived_at ?? '',
                    restarted_from_id: row?.restarted_from_id ?? null,
                }
            })
        },
        writeState(state) {
            this.isSyncing = true
            this.state = {
                phones: state.phones,
                sources: state.sources,
                campaigns: state.campaigns,
                archived_campaigns: state.archived_campaigns,
            }
            this.syncSelectionState(state)

            this.$nextTick(() => {
                this.isSyncing = false
            })
        },
        syncState() {
            const state = {
                phones: this.normalizePhones(this.state.phones),
                sources: this.normalizeSources(this.state.sources),
                campaigns: this.normalizeCampaigns(this.state.campaigns),
                archived_campaigns: this.normalizeCampaigns(this.state.archived_campaigns),
            }

            state.campaigns = this.dropConflictingSourceCampaignPhones(
                this.syncSourceOnlyCampaignRows(state).campaigns
            )

            this.writeState(state)
        },
        syncSelectionState(state) {
            const trackingKeys = new Set((state.campaigns || []).map((row) => row.key))
            this.selectedTrackingKeys = this.selectedTrackingKeys.filter((key) => trackingKeys.has(key))
        },
        currentDateTimeValue() {
            const date = new Date()
            const month = `${date.getMonth() + 1}`.padStart(2, '0')
            const day = `${date.getDate()}`.padStart(2, '0')
            const hours = `${date.getHours()}`.padStart(2, '0')
            const minutes = `${date.getMinutes()}`.padStart(2, '0')
            const seconds = `${date.getSeconds()}`.padStart(2, '0')

            return `${date.getFullYear()}-${month}-${day} ${hours}:${minutes}:${seconds}`
        },
        formatDateTime(value) {
            const text = String(value ?? '').trim().replace('T', ' ')

            if (! text) {
                return '—'
            }

            const [datePart, timePart = ''] = text.split(' ')
            const [year, month, day] = datePart.split('-')

            if (! year || ! month || ! day) {
                return text
            }

            return `${day}.${month}.${year}${timePart ? ` ${timePart.slice(0, 5)}` : ''}`
        },
        sourceOptions() {
            return this.state.sources
        },
        sourceRow(sourceKey) {
            return this.state.sources.find((row) => row.key === sourceKey) ?? null
        },
        sourceLabel(sourceKey) {
            const sourceRow = this.sourceRow(sourceKey)

            if (! sourceRow) {
                return '—'
            }

            return sourceRow.name ? `${sourceRow.source} (${sourceRow.name})` : sourceRow.source
        },
        campaignName(row) {
            if (row.type === 'source') {
                return this.sourceRow(row.source_key)?.name || '—'
            }

            return row.medium_name || '—'
        },
        campaignSourceValue(row) {
            return this.sourceRow(row.source_key)?.source || ''
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
        trackingLinkValue(campaignRow) {
            return this.buildUrl(
                this.campaignSourceValue(campaignRow),
                campaignRow.type === 'medium' ? campaignRow.medium : '',
                campaignRow.open_booking_widget,
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
        async copyTrackingLink(campaignRow) {
            const copied = await this.copyText(this.trackingLinkValue(campaignRow))

            if (! copied) {
                return
            }

            this.copiedTrackingKey = campaignRow.key

            if (this.copyResetTimer) {
                clearTimeout(this.copyResetTimer)
            }

            this.copyResetTimer = setTimeout(() => {
                if (this.copiedTrackingKey === campaignRow.key) {
                    this.copiedTrackingKey = null
                }
            }, 1500)
        },
        isTrackingLinkCopied(campaignRow) {
            return this.copiedTrackingKey === campaignRow.key
        },
        isTrackingSelected(campaignKey) {
            return this.selectedTrackingKeys.includes(campaignKey)
        },
        toggleTrackingSelection(campaignKey) {
            this.selectedTrackingKeys = this.isTrackingSelected(campaignKey)
                ? this.selectedTrackingKeys.filter((key) => key !== campaignKey)
                : [...this.selectedTrackingKeys, campaignKey]
        },
        visibleTrackingKeys() {
            return this.state.campaigns.map((row) => row.key)
        },
        areAllTrackingSelected() {
            const keys = this.visibleTrackingKeys()

            return keys.length > 0 && keys.every((key) => this.selectedTrackingKeys.includes(key))
        },
        toggleAllTrackingSelection() {
            const keys = this.visibleTrackingKeys()

            if (keys.length === 0) {
                return
            }

            if (this.areAllTrackingSelected()) {
                this.selectedTrackingKeys = this.selectedTrackingKeys.filter((key) => ! keys.includes(key))

                return
            }

            this.selectedTrackingKeys = Array.from(new Set([...this.selectedTrackingKeys, ...keys]))
        },
        selectedTrackingRows() {
            return this.state.campaigns.filter((row) => this.selectedTrackingKeys.includes(row.key))
        },
        clearTrackingSelection() {
            this.selectedTrackingKeys = []
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
            if (this.isPhoneReferenced(phoneKey)) {
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
            this.state.campaigns = this.state.campaigns.filter((row) => row.source_key !== sourceKey)
            this.state.archived_campaigns = this.state.archived_campaigns.filter((row) => row.source_key !== sourceKey)
            this.syncState()
        },
        syncSourceOnlyCampaignRows(state) {
            let campaigns = [...state.campaigns]
            const archivedCampaigns = [...state.archived_campaigns]

            state.sources.forEach((sourceRow) => {
                const sourceKey = sourceRow.key
                const defaultPhoneKey = sourceRow.default_phone_key || ''
                const activeSourceRows = campaigns.filter((row) => row.type === 'source' && row.source_key === sourceKey)
                const archivedSourceExists = archivedCampaigns.some((row) => row.type === 'source' && row.source_key === sourceKey)
                const shouldHaveRow = !! defaultPhoneKey && (activeSourceRows.length > 0 || ! archivedSourceExists)

                if (! shouldHaveRow) {
                    campaigns = campaigns.filter((row) => ! (row.type === 'source' && row.source_key === sourceKey))

                    return
                }

                if (! activeSourceRows.length) {
                    campaigns.unshift({
                        key: this.makeKey('campaign'),
                        id: null,
                        type: 'source',
                        source_key: sourceKey,
                        medium: '',
                        medium_name: '',
                        phone_key: defaultPhoneKey,
                        open_booking_widget: !! sourceRow.open_booking_widget,
                        started_at: this.currentDateTimeValue(),
                        stopped_at: '',
                        archived_at: '',
                        restarted_from_id: null,
                    })

                    return
                }

                const primaryKey = activeSourceRows[0].key

                campaigns = campaigns
                    .filter((row) => ! (row.type === 'source' && row.source_key === sourceKey && row.key !== primaryKey))
                    .map((row) => {
                        if (row.type === 'source' && row.source_key === sourceKey) {
                            return {
                                ...row,
                                phone_key: defaultPhoneKey,
                            }
                        }

                        return row
                    })
            })

            return {
                ...state,
                campaigns,
            }
        },
        dropConflictingSourceCampaignPhones(campaignRows) {
            const mediumPhoneKeys = campaignRows
                .filter((row) => row.type === 'medium' && row.phone_key)
                .map((row) => row.phone_key)

            if (mediumPhoneKeys.length === 0) {
                return campaignRows
            }

            return campaignRows.map((row) => {
                if (row.type === 'source' && mediumPhoneKeys.includes(row.phone_key)) {
                    return {
                        ...row,
                        phone_key: '',
                    }
                }

                return row
            })
        },
        addMediumCampaign() {
            this.state.campaigns.unshift({
                key: this.makeKey('campaign'),
                id: null,
                type: 'medium',
                source_key: this.state.sources[0]?.key ?? '',
                medium: '',
                medium_name: '',
                phone_key: '',
                open_booking_widget: false,
                started_at: this.currentDateTimeValue(),
                stopped_at: '',
                archived_at: '',
                restarted_from_id: null,
            })

            this.activeTab = 'tracking'
            this.syncState()
        },
        async runAction(callback, activeTab = null) {
            this.isPersisting = true

            try {
                await callback()
            } finally {
                if (activeTab) {
                    this.activeTab = activeTab
                }

                this.isPersisting = false
            }
        },
        async requestSave() {
            this.syncState()
            await this.runAction(() => this.$wire.saveState(this.state))
        },
        async requestStopCampaign(campaignKey) {
            this.syncState()
            await this.runAction(() => this.$wire.stopCampaign(campaignKey, this.state), 'tracking')
        },
        async requestStopSelectedCampaigns() {
            const selectedKeys = [...this.selectedTrackingKeys]

            if (selectedKeys.length === 0) {
                return
            }

            this.syncState()
            await this.runAction(async () => {
                await this.$wire.stopCampaigns(selectedKeys, this.state)
                this.clearTrackingSelection()
            }, 'tracking')
        },
        async requestResumeCampaign(campaignKey) {
            this.syncState()
            await this.runAction(() => this.$wire.resumeCampaign(campaignKey, this.state), 'archive')
        },
        async requestDeleteCampaign(campaignKey) {
            this.syncState()
            await this.runAction(() => this.$wire.deleteCampaign(campaignKey, this.state), 'tracking')
        },
        async requestDeleteArchivedCampaign(campaignKey) {
            this.syncState()
            await this.runAction(() => this.$wire.deleteArchivedCampaign(campaignKey, this.state), 'archive')
        },
        isPhoneBusyInActiveCampaigns(phoneKey, context = {}) {
            if (! phoneKey) {
                return false
            }

            return this.state.campaigns.some((row) => {
                if (row.phone_key !== phoneKey) {
                    return false
                }

                return ! (context.type === 'campaign' && context.key === row.key)
            })
        },
        activeCampaignUsageLabel(row) {
            if (row.type === 'source') {
                return this.sourceLabel(row.source_key)
            }

            return `${this.sourceLabel(row.source_key)} / ${row.medium || '—'}`
        },
        activeCampaignsByPhone(phoneKey) {
            if (! phoneKey) {
                return []
            }

            return this.state.campaigns.filter((row) => row.phone_key === phoneKey)
        },
        isDuplicatePhoneInActiveCampaigns(phoneKey) {
            return this.activeCampaignsByPhone(phoneKey).length > 1
        },
        isDuplicateCampaignPhone(campaignRow) {
            return this.isDuplicatePhoneInActiveCampaigns(campaignRow?.phone_key)
        },
        isPhoneOptionDisabled(phoneKey, campaignRow) {
            if (campaignRow?.phone_key === phoneKey) {
                return false
            }

            return this.isPhoneBusyInActiveCampaigns(phoneKey, { type: 'campaign', key: campaignRow?.key })
                || this.isPhoneUsedBySourceTemplateForCampaign(phoneKey, campaignRow)
        },
        phoneOptionLabel(phoneKey, campaignRow) {
            const phone = this.phoneLabel(phoneKey)

            return this.isPhoneOptionDisabled(phoneKey, campaignRow) ? `${phone} (занят)` : phone
        },
        isSourcePhoneOptionDisabled(phoneKey, sourceRow) {
            if (sourceRow?.default_phone_key === phoneKey) {
                return false
            }

            return this.isPhoneBusyInActiveCampaigns(phoneKey) || this.isPhoneUsedByOtherSourceTemplate(phoneKey, sourceRow)
        },
        sourcePhoneOptionLabel(phoneKey, sourceRow) {
            const phone = this.phoneLabel(phoneKey)

            return this.isSourcePhoneOptionDisabled(phoneKey, sourceRow) ? `${phone} (занят)` : phone
        },
        isPhoneUsedByOtherSourceTemplate(phoneKey, sourceRow = null) {
            if (! phoneKey) {
                return false
            }

            return this.state.sources.some((row) => {
                if (row.default_phone_key !== phoneKey) {
                    return false
                }

                return ! sourceRow || row.key !== sourceRow.key
            })
        },
        isPhoneUsedBySourceTemplateForCampaign(phoneKey, campaignRow = null) {
            if (! phoneKey) {
                return false
            }

            return this.state.sources.some((row) => {
                if (row.default_phone_key !== phoneKey) {
                    return false
                }

                return ! campaignRow || row.key !== campaignRow.source_key
            })
        },
        phoneLabel(phoneKey) {
            return this.state.phones.find((row) => row.key === phoneKey)?.phone ?? '—'
        },
        isPhoneReferenced(phoneKey) {
            if (! phoneKey) {
                return false
            }

            return this.state.sources.some((row) => row.default_phone_key === phoneKey)
                || this.state.campaigns.some((row) => row.phone_key === phoneKey)
                || this.state.archived_campaigns.some((row) => row.phone_key === phoneKey)
        },
        isPhoneBusyForDisplay(phoneKey) {
            return this.state.campaigns.some((row) => row.phone_key === phoneKey)
                || this.state.sources.some((row) => row.default_phone_key === phoneKey)
        },
        phoneUsage(phoneKey) {
            const activeCampaigns = this.activeCampaignsByPhone(phoneKey)

            if (activeCampaigns.length) {
                return activeCampaigns
                    .map((row) => this.activeCampaignUsageLabel(row))
                    .join(' • ')
            }

            const sourceTemplate = this.state.sources.find((row) => row.default_phone_key === phoneKey)

            if (sourceTemplate) {
                return `Шаблон source: ${sourceTemplate.source || '—'}`
            }

            const archivedCampaign = this.state.archived_campaigns.find((row) => row.phone_key === phoneKey)

            if (archivedCampaign) {
                if (archivedCampaign.type === 'source') {
                    return `Архив: ${this.sourceLabel(archivedCampaign.source_key)}`
                }

                return `Архив: ${this.sourceLabel(archivedCampaign.source_key)} / ${archivedCampaign.medium || '—'}`
            }

            return 'Свободен'
        },
    }"
    class="w-full rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900"
>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-white/10">
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
                x-bind:class="activeTab === 'archive' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-200'"
                x-on:click="activeTab = 'archive'"
            >
                Архив
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

        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-md bg-primary-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-60"
            x-bind:disabled="isPersisting"
            x-on:click="requestSave()"
        >
            <span x-show="! isPersisting">Сохранить UTM</span>
            <span x-show="isPersisting">Сохранение...</span>
        </button>
    </div>

    <div class="p-4">
        <div x-show="activeTab === 'tracking'" x-cloak class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div x-show="selectedTrackingKeys.length > 0" x-cloak class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="`Выбрано: ${selectedTrackingKeys.length}`"></span>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-60"
                        x-bind:disabled="isPersisting"
                        x-on:click="requestStopSelectedCampaigns()"
                    >
                        Остановить выбранные
                    </button>

                    <button
                        type="button"
                        class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 disabled:cursor-not-allowed disabled:opacity-60"
                        x-bind:disabled="isPersisting"
                        x-on:click="clearTrackingSelection()"
                    >
                        Сбросить
                    </button>
                </div>

                <button
                    type="button"
                    class="ml-auto text-sm text-primary-600 hover:underline dark:text-primary-400 disabled:cursor-not-allowed disabled:opacity-60"
                    x-bind:disabled="isPersisting"
                    x-on:click="addMediumCampaign()"
                >
                    + добавить medium
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1160px] table-fixed border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="w-[4%] py-2 pr-2 text-left font-medium text-gray-500 dark:text-gray-400">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent"
                                    x-bind:checked="areAllTrackingSelected()"
                                    x-bind:disabled="isPersisting || state.campaigns.length === 0"
                                    x-init="$el.indeterminate = selectedTrackingKeys.length > 0 && ! areAllTrackingSelected()"
                                    x-effect="$el.indeterminate = selectedTrackingKeys.length > 0 && ! areAllTrackingSelected()"
                                    x-on:change="toggleAllTrackingSelection()"
                                />
                            </th>
                            <th class="w-[12%] py-2 pr-2 text-left font-medium text-gray-500 dark:text-gray-400">Source</th>
                            <th class="w-[7%] py-2 pr-2 text-left font-medium text-gray-500 dark:text-gray-400">Medium</th>
                            <th class="w-[6%] py-2 pr-2 text-center font-medium text-gray-500 dark:text-gray-400">Виджет</th>
                            <th class="w-[14%] py-2 pr-2 text-left font-medium text-gray-500 dark:text-gray-400">Ссылка</th>
                            <th class="w-[9%] py-2 pr-2 text-left font-medium text-gray-500 dark:text-gray-400">Название</th>
                            <th class="w-[13%] py-2 pr-2 text-left font-medium text-gray-500 dark:text-gray-400">Телефон</th>
                            <th class="w-[8%] py-2 pr-2 text-left font-medium text-gray-500 dark:text-gray-400">Запуск</th>
                            <th class="w-[8%] py-2 pr-2 text-left font-medium text-gray-500 dark:text-gray-400">Остановка</th>
                            <th class="w-[4%] py-2 pr-2 text-left font-medium text-gray-500 dark:text-gray-400">Статус</th>
                            <th class="py-2 text-right font-medium text-gray-500 dark:text-gray-400">Действия</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-if="state.campaigns.length === 0">
                            <tr>
                                <td colspan="11" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Пока нет ни одной активной UTM-кампании.
                                </td>
                            </tr>
                        </template>

                        <template x-for="campaignRow in state.campaigns" :key="campaignRow.key">
                            <tr
                                class="border-b border-gray-200 dark:border-white/10"
                                x-bind:class="isDuplicateCampaignPhone(campaignRow) ? 'bg-rose-50 dark:bg-rose-500/10' : ''"
                            >
                                <td class="py-2 pr-2 align-top">
                                    <input
                                        type="checkbox"
                                        class="mt-2 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent"
                                        x-bind:checked="isTrackingSelected(campaignRow.key)"
                                        x-bind:disabled="isPersisting"
                                        x-on:change="toggleTrackingSelection(campaignRow.key)"
                                    />
                                </td>

                                <td class="py-2 pr-2 align-top">
                                    <select
                                        class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                        x-model="campaignRow.source_key"
                                        x-bind:disabled="isPersisting"
                                        x-on:change="syncState()"
                                    >
                                        <option value="">Выберите source</option>
                                        <template x-for="sourceRow in sourceOptions()" :key="sourceRow.key">
                                            <option
                                                x-bind:selected="campaignRow.source_key === sourceRow.key"
                                                x-bind:value="sourceRow.key"
                                                x-text="sourceRow.name ? `${sourceRow.source} (${sourceRow.name})` : sourceRow.source"
                                            ></option>
                                        </template>
                                    </select>
                                </td>

                                <td class="py-2 pr-2 align-top">
                                    <template x-if="campaignRow.type === 'source'">
                                        <div class="rounded-md border border-transparent px-2 py-1 text-xs text-gray-400 dark:text-gray-500">—</div>
                                    </template>

                                    <template x-if="campaignRow.type === 'medium'">
                                        <input
                                            type="text"
                                            class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                            x-model="campaignRow.medium"
                                            x-bind:disabled="isPersisting"
                                            x-on:input.debounce.300ms="syncState()"
                                            placeholder="cpc"
                                        />
                                    </template>
                                </td>

                                <td class="py-2 pr-2 align-top text-center">
                                    <label class="inline-flex h-8 items-center justify-center">
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent"
                                            x-model="campaignRow.open_booking_widget"
                                            x-bind:disabled="isPersisting"
                                            x-on:change="syncState()"
                                        />
                                    </label>
                                </td>

                                <td class="py-2 pr-2 align-top">
                                    <div class="space-y-1">
                                        <div class="relative group">
                                            <input
                                                type="text"
                                                readonly
                                                class="block w-full rounded-md border-gray-300 bg-gray-50 px-2 py-1.5 text-xs shadow-none transition cursor-copy dark:border-white/10 dark:bg-white/5 dark:text-white"
                                                x-bind:class="isTrackingLinkCopied(campaignRow) ? 'border-emerald-400 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-300 dark:border-emerald-500/60 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30' : ''"
                                                x-bind:style="isTrackingLinkCopied(campaignRow) ? 'border-color:#16a34a !important; color:#15803d !important; background-color:#ecfdf5 !important; box-shadow:0 0 0 1px #16a34a inset !important;' : ''"
                                                x-bind:value="trackingLinkValue(campaignRow)"
                                                x-bind:title="isTrackingLinkCopied(campaignRow) ? 'Скопировано' : 'Кликните, чтобы скопировать ссылку'"
                                                x-on:click="copyTrackingLink(campaignRow)"
                                            />

                                            <div
                                                x-show="! isTrackingLinkCopied(campaignRow)"
                                                x-cloak
                                                class="pointer-events-none absolute left-2 top-full z-10 mt-1 rounded-md bg-gray-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 shadow-sm transition group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-white dark:text-gray-900"
                                            >
                                                Кликните, чтобы скопировать
                                            </div>
                                        </div>

                                        <p
                                            x-show="isTrackingLinkCopied(campaignRow)"
                                            x-cloak
                                            class="font-medium"
                                            style="font-size:10px; line-height:12px; color:#16a34a;"
                                        >
                                            Скопировано
                                        </p>
                                    </div>
                                </td>

                                <td class="py-2 pr-2 align-top">
                                    <template x-if="campaignRow.type === 'source'">
                                        <div class="rounded-md border border-transparent px-2 py-1 text-xs text-gray-900 dark:text-white" x-text="campaignName(campaignRow)"></div>
                                    </template>

                                    <template x-if="campaignRow.type === 'medium'">
                                        <input
                                            type="text"
                                            class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                            x-model="campaignRow.medium_name"
                                            x-bind:disabled="isPersisting"
                                            x-on:input.debounce.300ms="syncState()"
                                            placeholder="Google CPC"
                                        />
                                    </template>
                                </td>

                                <td class="py-2 pr-2 align-top">
                                    <select
                                        class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                        x-bind:class="isDuplicateCampaignPhone(campaignRow) ? 'border-rose-500 bg-rose-50 text-rose-700 ring-1 ring-rose-400 focus:border-rose-500 focus:ring-rose-500 dark:border-rose-500/70 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/40' : ''"
                                        x-bind:style="isDuplicateCampaignPhone(campaignRow) ? 'border-color:#dc2626 !important; color:#b91c1c !important; background-color:#fef2f2 !important; box-shadow:0 0 0 1px #dc2626 inset !important;' : ''"
                                        x-model="campaignRow.phone_key"
                                        x-bind:disabled="isPersisting"
                                        x-on:change="syncState()"
                                    >
                                        <option value="">Без телефона</option>
                                        <template x-for="phoneRow in state.phones" :key="phoneRow.key">
                                            <option
                                                x-bind:disabled="isPhoneOptionDisabled(phoneRow.key, campaignRow)"
                                                x-bind:selected="campaignRow.phone_key === phoneRow.key"
                                                x-bind:value="phoneRow.key"
                                                x-text="phoneOptionLabel(phoneRow.key, campaignRow)"
                                            ></option>
                                        </template>
                                    </select>

                                    <p
                                        x-show="isDuplicateCampaignPhone(campaignRow)"
                                        x-cloak
                                        class="mt-1 font-medium"
                                        style="font-size:10px; line-height:12px; color:#dc2626;"
                                    >
                                        Дубликат телефона в активных кампаниях
                                    </p>
                                </td>

                                <td class="py-2 pr-2 align-top text-xs text-gray-700 dark:text-gray-300" x-text="formatDateTime(campaignRow.started_at)"></td>
                                <td class="py-2 pr-2 align-top text-xs text-gray-400 dark:text-gray-500">—</td>

                                <td class="py-2 pr-2 align-top">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200" title="активна">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true">
                                            <path
                                                fill-rule="evenodd"
                                                d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </span>
                                </td>

                                <td class="py-2 text-right align-top">
                                    <div class="flex justify-end gap-2 whitespace-nowrap">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-60"
                                            x-bind:disabled="isPersisting"
                                            x-on:click="requestStopCampaign(campaignRow.key)"
                                            title="Остановить"
                                            aria-label="Остановить"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="#374151" aria-hidden="true">
                                                <path d="M6 6.75A.75.75 0 0 1 6.75 6h6.5a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-.75.75h-6.5A.75.75 0 0 1 6 13.25v-6.5Z" />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60"
                                            x-bind:disabled="isPersisting"
                                            x-on:click="requestDeleteCampaign(campaignRow.key)"
                                            title="Удалить в архив"
                                            aria-label="Удалить в архив"
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
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="activeTab === 'archive'" x-cloak class="space-y-3">
            <div class="overflow-x-auto">
                <table class="w-full table-fixed border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="w-[15%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Source</th>
                            <th class="w-[10%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Medium</th>
                            <th class="w-[6%] py-2 pr-3 text-center font-medium text-gray-500 dark:text-gray-400">Виджет</th>
                            <th class="w-[18%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Ссылка</th>
                            <th class="w-[12%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Название</th>
                            <th class="w-[12%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Телефон</th>
                            <th class="w-[10%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Запуск</th>
                            <th class="w-[10%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Остановка</th>
                            <th class="w-[4%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Статус</th>
                            <th class="py-2 text-right font-medium text-gray-500 dark:text-gray-400">Действия</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-if="state.archived_campaigns.length === 0">
                            <tr>
                                <td colspan="10" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Архив пока пуст.
                                </td>
                            </tr>
                        </template>

                        <template x-for="campaignRow in state.archived_campaigns" :key="campaignRow.key">
                            <tr class="border-b border-gray-200 dark:border-white/10">
                                <td class="py-2 pr-3 align-top text-xs text-gray-700 dark:text-gray-300" x-text="sourceLabel(campaignRow.source_key)"></td>
                                <td class="py-2 pr-3 align-top text-xs text-gray-700 dark:text-gray-300" x-text="campaignRow.type === 'medium' ? (campaignRow.medium || '—') : '—'"></td>

                                <td class="py-2 pr-3 align-top text-center">
                                    <span class="inline-flex h-8 items-center justify-center text-xs text-gray-700 dark:text-gray-300" x-text="campaignRow.open_booking_widget ? 'Да' : 'Нет'"></span>
                                </td>

                                <td class="py-2 pr-3 align-top">
                                    <div class="space-y-1">
                                        <div class="relative group">
                                            <input
                                                type="text"
                                                readonly
                                                class="block w-full rounded-md border-gray-300 bg-gray-50 px-2 py-1 text-xs shadow-none transition cursor-copy dark:border-white/10 dark:bg-white/5 dark:text-white"
                                                x-bind:class="isTrackingLinkCopied(campaignRow) ? 'border-emerald-400 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-300 dark:border-emerald-500/60 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30' : ''"
                                                x-bind:style="isTrackingLinkCopied(campaignRow) ? 'border-color:#16a34a !important; color:#15803d !important; background-color:#ecfdf5 !important; box-shadow:0 0 0 1px #16a34a inset !important;' : ''"
                                                x-bind:value="trackingLinkValue(campaignRow)"
                                                x-bind:title="isTrackingLinkCopied(campaignRow) ? 'Скопировано' : 'Кликните, чтобы скопировать ссылку'"
                                                x-on:click="copyTrackingLink(campaignRow)"
                                            />

                                            <div
                                                x-show="! isTrackingLinkCopied(campaignRow)"
                                                x-cloak
                                                class="pointer-events-none absolute left-2 top-full z-10 mt-1 rounded-md bg-gray-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 shadow-sm transition group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-white dark:text-gray-900"
                                            >
                                                Кликните, чтобы скопировать
                                            </div>
                                        </div>

                                        <p
                                            x-show="isTrackingLinkCopied(campaignRow)"
                                            x-cloak
                                            class="font-medium"
                                            style="font-size:10px; line-height:12px; color:#16a34a;"
                                        >
                                            Скопировано
                                        </p>
                                    </div>
                                </td>

                                <td class="py-2 pr-3 align-top text-xs text-gray-700 dark:text-gray-300" x-text="campaignName(campaignRow)"></td>
                                <td class="py-2 pr-3 align-top text-xs text-gray-700 dark:text-gray-300" x-text="phoneLabel(campaignRow.phone_key)"></td>
                                <td class="py-2 pr-3 align-top text-xs text-gray-700 dark:text-gray-300" x-text="formatDateTime(campaignRow.started_at)"></td>
                                <td class="py-2 pr-3 align-top text-xs text-gray-700 dark:text-gray-300" x-text="formatDateTime(campaignRow.stopped_at || campaignRow.archived_at)"></td>

                                <td class="py-2 pr-3 align-top">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-200" title="остановлена">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true">
                                            <path
                                                fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </span>
                                </td>

                                <td class="py-2 text-right align-top">
                                    <div class="flex justify-end gap-2 whitespace-nowrap">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-emerald-200 text-emerald-600 transition hover:bg-emerald-50 dark:border-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/10 disabled:cursor-not-allowed disabled:opacity-60"
                                            x-bind:disabled="isPersisting"
                                            x-on:click="requestResumeCampaign(campaignRow.key)"
                                            title="Возобновить"
                                            aria-label="Возобновить"
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
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60"
                                            x-bind:disabled="isPersisting"
                                            x-on:click="requestDeleteArchivedCampaign(campaignRow.key)"
                                            title="Удалить"
                                            aria-label="Удалить"
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
                    class="text-sm text-primary-600 hover:underline dark:text-primary-400 disabled:cursor-not-allowed disabled:opacity-60"
                    x-bind:disabled="isPersisting"
                    x-on:click="addSource()"
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
                                        class="block w-full rounded-md border-gray-300 px-2 py-1 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                        x-model="sourceRow.source"
                                        x-bind:disabled="isPersisting"
                                        x-on:input.debounce.300ms="syncState()"
                                        placeholder="google"
                                    />
                                </td>

                                <td class="py-2 pr-3 align-top">
                                    <input
                                        type="text"
                                        class="block w-full rounded-md border-gray-300 px-2 py-1 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                        x-model="sourceRow.name"
                                        x-bind:disabled="isPersisting"
                                        x-on:input.debounce.300ms="syncState()"
                                        placeholder="Google Ads"
                                    />
                                </td>

                                <td class="py-2 pr-3 align-top">
                                    <select
                                        class="block w-full rounded-md border-gray-300 px-2 py-1 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                        x-model="sourceRow.default_phone_key"
                                        x-bind:disabled="isPersisting"
                                        x-on:change="syncState()"
                                    >
                                        <option value="">Без дефолтного телефона</option>
                                        <template x-for="phoneRow in state.phones" :key="phoneRow.key">
                                            <option
                                                x-bind:disabled="isSourcePhoneOptionDisabled(phoneRow.key, sourceRow)"
                                                x-bind:selected="sourceRow.default_phone_key === phoneRow.key"
                                                x-bind:value="phoneRow.key"
                                                x-text="sourcePhoneOptionLabel(phoneRow.key, sourceRow)"
                                            ></option>
                                        </template>
                                    </select>
                                </td>

                                <td class="py-2 text-right align-top">
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60"
                                        x-bind:disabled="isPersisting"
                                        x-on:click="deleteSource(sourceRow.key)"
                                        title="Удалить"
                                        aria-label="Удалить"
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
                    class="text-sm text-primary-600 hover:underline dark:text-primary-400 disabled:cursor-not-allowed disabled:opacity-60"
                    x-bind:disabled="isPersisting"
                    x-on:click="addPhone()"
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
                                        class="block w-full rounded-md border-gray-300 px-2 py-1 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                        x-bind:class="isDuplicatePhoneInActiveCampaigns(phoneRow.key) ? 'border-rose-500 bg-rose-50 text-rose-700 ring-1 ring-rose-400 focus:border-rose-500 focus:ring-rose-500 dark:border-rose-500/70 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/40' : ''"
                                        x-bind:style="isDuplicatePhoneInActiveCampaigns(phoneRow.key) ? 'border-color:#dc2626 !important; color:#b91c1c !important; background-color:#fef2f2 !important; box-shadow:0 0 0 1px #dc2626 inset !important;' : ''"
                                        x-model="phoneRow.phone"
                                        x-bind:disabled="isPersisting"
                                        x-on:input.debounce.300ms="syncState()"
                                        placeholder="+7 (999) 000-00-00"
                                    />
                                </td>

                                <td class="py-2 pr-3 align-top">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                        x-bind:class="isDuplicatePhoneInActiveCampaigns(phoneRow.key) ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-200' : (isPhoneBusyForDisplay(phoneRow.key) ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200')"
                                        x-text="isDuplicatePhoneInActiveCampaigns(phoneRow.key) ? 'дубль' : (isPhoneBusyForDisplay(phoneRow.key) ? 'занят' : 'свободен')"
                                    ></span>
                                </td>

                                <td
                                    class="py-2 pr-3 align-top text-gray-600 dark:text-gray-300"
                                    x-bind:class="isDuplicatePhoneInActiveCampaigns(phoneRow.key) ? 'font-medium' : ''"
                                    x-bind:style="isDuplicatePhoneInActiveCampaigns(phoneRow.key) ? 'font-size:10px; line-height:12px; color:#dc2626;' : ''"
                                    x-text="phoneUsage(phoneRow.key)"
                                ></td>

                                <td class="py-2 text-right align-top">
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border transition disabled:cursor-not-allowed disabled:opacity-60"
                                        x-bind:class="isPhoneReferenced(phoneRow.key) ? 'cursor-not-allowed border-gray-200 text-gray-400 dark:border-white/10 dark:text-gray-500' : 'border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10'"
                                        x-bind:disabled="isPersisting"
                                        x-on:click="deletePhone(phoneRow.key)"
                                        title="Удалить"
                                        aria-label="Удалить"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 20 20"
                                            x-bind:fill="isPhoneReferenced(phoneRow.key) ? '#9ca3af' : '#dc2626'"
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
