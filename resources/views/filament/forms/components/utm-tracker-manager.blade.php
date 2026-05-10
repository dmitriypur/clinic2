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
        copiedPhoneContext: null,
        copyResetTimer: null,
        phoneCopyResetTimer: null,
        selectedTrackingKeys: [],
        trackingSearch: '',
        archiveSearch: '',
        trackingFiltersOpen: false,
        archiveFiltersOpen: false,
        trackingFilters: {
            source_key: '',
            phone_key: '',
            started_date: '',
        },
        archiveFilters: {
            source_key: '',
            phone_key: '',
            started_date: '',
        },
        trackingSort: {
            column: 'created_at',
            direction: 'desc',
        },
        archiveSort: {
            column: 'created_at',
            direction: 'desc',
        },
        expandedSources: {
            tracking: [],
            archive: [],
        },
        expandedMediums: {
            tracking: [],
            archive: [],
        },
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
                is_organic: !! row?.is_organic,
            }))
        },
        normalizeCampaigns(rows) {
            return (Array.isArray(rows) ? rows : []).map((row) => {
                const campaign = row?.campaign ?? ''
                const medium = row?.medium ?? ''
                const type = row?.type === 'source'
                    ? 'source'
                    : (row?.type === 'campaign' ? 'campaign' : (campaign ? 'campaign' : (medium || row?.type === 'medium' ? 'medium' : 'source')))

                return {
                    key: row?.key ?? this.makeKey('campaign'),
                    id: row?.id ?? null,
                    type,
                    source_key: row?.source_key ?? '',
                    medium: ['medium', 'campaign'].includes(type) ? medium : '',
                    medium_name: ['medium', 'campaign'].includes(type) ? (row?.medium_name ?? '') : '',
                    campaign: ['medium', 'campaign'].includes(type) ? campaign : '',
                    campaign_name: ['medium', 'campaign'].includes(type) ? (row?.campaign_name ?? '') : '',
                    phone_key: row?.phone_key ?? '',
                    open_booking_widget: !! row?.open_booking_widget,
                    is_organic: !! row?.is_organic,
                    is_organic_overridden: !! row?.is_organic_overridden,
                    effective_is_organic: !! row?.effective_is_organic,
                    created_at: row?.created_at ?? '',
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

            if (row.type === 'campaign') {
                return row.campaign_name || '—'
            }

            return row.medium_name || '—'
        },
        campaignSourceValue(row) {
            return this.sourceRow(row.source_key)?.source || ''
        },
        campaignSourceIsOrganic(row) {
            return !! this.sourceRow(row.source_key)?.is_organic
        },
        campaignEffectiveIsOrganic(row) {
            return row?.is_organic_overridden ? !! row?.is_organic : this.campaignSourceIsOrganic(row)
        },
        setCampaignOrganic(row, value) {
            const nextValue = !! value
            const sourceValue = this.campaignSourceIsOrganic(row)

            row.is_organic = nextValue === sourceValue ? false : nextValue
            row.is_organic_overridden = nextValue !== sourceValue
            row.effective_is_organic = nextValue
            this.syncState()
        },
        buildUrl(sourceValue = '', mediumValue = '', campaignValue = '', shouldOpenWidget = false) {
            const source = String(sourceValue || '').trim()
            const medium = String(mediumValue || '').trim()
            const campaign = String(campaignValue || '').trim()

            if (! source) {
                return this.trackingBaseUrl
            }

            const url = new URL(this.trackingBaseUrl)

            url.searchParams.set('utm_source', source)

            if (medium) {
                url.searchParams.set('utm_medium', medium)
            }

            if (campaign) {
                url.searchParams.set('utm_campaign', campaign)
            }

            if (shouldOpenWidget) {
                url.hash = 'appointment-form'
            }

            return url.toString()
        },
        trackingLinkValue(campaignRow) {
            return this.buildUrl(
                this.campaignSourceValue(campaignRow),
                ['medium', 'campaign'].includes(campaignRow.type) ? campaignRow.medium : '',
                campaignRow.type === 'campaign' ? campaignRow.campaign : '',
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
        async copySelectedPhone(phoneKey, contextKey) {
            const copied = await this.copyText(this.phoneLabel(phoneKey))

            if (! copied) {
                return
            }

            this.copiedPhoneContext = contextKey

            if (this.phoneCopyResetTimer) {
                clearTimeout(this.phoneCopyResetTimer)
            }

            this.phoneCopyResetTimer = setTimeout(() => {
                if (this.copiedPhoneContext === contextKey) {
                    this.copiedPhoneContext = null
                }
            }, 1500)
        },
        isPhoneCopied(contextKey) {
            return this.copiedPhoneContext === contextKey
        },
        campaignCreatedAt(row) {
            return String(row?.created_at || row?.started_at || '').trim()
        },
        campaignStatus(row, mode = 'tracking') {
            return mode === 'archive' || row?.archived_at ? 'stopped' : 'active'
        },
        campaignSearchText(row) {
            return [
                this.sourceLabel(row?.source_key),
                row?.medium || '',
                row?.campaign || '',
                this.campaignName(row),
                this.phoneLabel(row?.phone_key),
                this.trackingLinkValue(row),
            ].join(' ').toLowerCase()
        },
        dateMatchesFilter(value, filterValue) {
            const normalizedFilter = String(filterValue || '').trim()

            if (! normalizedFilter) {
                return true
            }

            return String(value || '').startsWith(normalizedFilter)
        },
        rowMatchesCampaignFilters(row, mode = 'tracking') {
            const filters = mode === 'archive' ? this.archiveFilters : this.trackingFilters
            const search = String(mode === 'archive' ? this.archiveSearch : this.trackingSearch).trim().toLowerCase()

            if (search && ! this.campaignSearchText(row).includes(search)) {
                return false
            }

            if (filters.source_key && row.source_key !== filters.source_key) {
                return false
            }

            if (filters.phone_key && row.phone_key !== filters.phone_key) {
                return false
            }

            if (filters.status && this.campaignStatus(row, mode) !== filters.status) {
                return false
            }

            if (! this.dateMatchesFilter(row.started_at, filters.started_date)) {
                return false
            }

            return true
        },
        campaignSortComparable(row, column, mode = 'tracking') {
            switch (column) {
                case 'source':
                    return this.sourceLabel(row.source_key).toLowerCase()
                case 'phone':
                    return this.phoneLabel(row.phone_key).toLowerCase()
                case 'started_at':
                    return row.started_at || ''
                case 'status':
                    return this.campaignStatus(row, mode)
                case 'created_at':
                default:
                    return this.campaignCreatedAt(row)
            }
        },
        sortCampaignRows(rows, mode = 'tracking') {
            const sort = mode === 'archive' ? this.archiveSort : this.trackingSort
            const multiplier = sort.direction === 'asc' ? 1 : -1

            return [...rows].sort((left, right) => {
                const leftValue = this.campaignSortComparable(left, sort.column, mode)
                const rightValue = this.campaignSortComparable(right, sort.column, mode)

                if (leftValue === rightValue) {
                    return 0
                }

                return (leftValue > rightValue ? 1 : -1) * multiplier
            })
        },
        campaignRowsForView(mode = 'tracking') {
            const rows = mode === 'archive' ? this.state.archived_campaigns : this.state.campaigns

            return this.sortCampaignRows(
                rows.filter((row) => this.rowMatchesCampaignFilters(row, mode)),
                mode,
            )
        },
        treeForView(mode = 'tracking') {
            const sourceMap = new Map()

            this.campaignRowsForView(mode).forEach((row) => {
                const sourceKey = row.source_key || '__empty_source__'

                if (! sourceMap.has(sourceKey)) {
                    const sourceRow = this.sourceRow(sourceKey) ?? {
                        key: sourceKey,
                        id: null,
                        source: '',
                        name: '',
                        default_phone_key: '',
                        open_booking_widget: false,
                        is_organic: false,
                    }

                    sourceMap.set(sourceKey, {
                        key: sourceKey,
                        source: sourceRow,
                        sourceRule: null,
                        mediums: new Map(),
                        looseCampaigns: [],
                    })
                }

                const sourceNode = sourceMap.get(sourceKey)

                if (row.type === 'source') {
                    sourceNode.sourceRule = row

                    return
                }

                const mediumKey = row.medium || '__empty_medium__'

                if (! sourceNode.mediums.has(mediumKey)) {
                    sourceNode.mediums.set(mediumKey, {
                        key: mediumKey,
                        medium: row.medium || '',
                        mediumRow: null,
                        campaigns: [],
                    })
                }

                const mediumNode = sourceNode.mediums.get(mediumKey)

                if (row.type === 'campaign') {
                    mediumNode.campaigns.push(row)

                    return
                }

                mediumNode.mediumRow = row
            })

            return Array.from(sourceMap.values())
                .map((sourceNode) => {
                    if (mode === 'tracking' && sourceNode.sourceRule) {
                        sourceNode.source.phone_key = sourceNode.sourceRule.phone_key || sourceNode.source.default_phone_key || ''
                        sourceNode.source.default_phone_key = sourceNode.source.phone_key
                        sourceNode.source.open_booking_widget = !! sourceNode.sourceRule.open_booking_widget
                    } else {
                        sourceNode.source.phone_key = sourceNode.source.default_phone_key || ''
                    }

                    return {
                        ...sourceNode,
                        mediums: Array.from(sourceNode.mediums.values())
                        .map((mediumNode) => ({
                            ...mediumNode,
                            campaigns: this.sortCampaignRows(mediumNode.campaigns, mode),
                        }))
                        .sort((left, right) => String(left.medium).localeCompare(String(right.medium), undefined, { numeric: true, sensitivity: 'base' })),
                    }
                })
                .sort((left, right) => this.sourceLabel(left.key).localeCompare(this.sourceLabel(right.key), undefined, { numeric: true, sensitivity: 'base' }))
        },
        hasTreeFilters(mode = 'tracking') {
            const filters = mode === 'archive' ? this.archiveFilters : this.trackingFilters
            const search = String(mode === 'archive' ? this.archiveSearch : this.trackingSearch).trim()

            return !! search || !! filters.source_key || !! filters.phone_key || !! filters.started_date
        },
        mediumTreeKey(sourceKey, medium) {
            return `${sourceKey}::${medium || '__empty_medium__'}`
        },
        isSourceExpanded(mode, sourceKey) {
            return this.hasTreeFilters(mode) || this.expandedSources[mode].includes(sourceKey)
        },
        toggleSource(mode, sourceKey) {
            this.expandedSources[mode] = this.expandedSources[mode].includes(sourceKey)
                ? this.expandedSources[mode].filter((key) => key !== sourceKey)
                : [...this.expandedSources[mode], sourceKey]
        },
        isMediumExpanded(mode, sourceKey, medium) {
            const key = this.mediumTreeKey(sourceKey, medium)

            return this.hasTreeFilters(mode) || this.expandedMediums[mode].includes(key)
        },
        toggleMedium(mode, sourceKey, medium) {
            const key = this.mediumTreeKey(sourceKey, medium)

            this.expandedMediums[mode] = this.expandedMediums[mode].includes(key)
                ? this.expandedMediums[mode].filter((value) => value !== key)
                : [...this.expandedMediums[mode], key]
        },
        sourceFallbackRow(sourceRow) {
            if (! sourceRow) {
                return null
            }

            return this.state.campaigns.find((row) => row.type === 'source' && row.source_key === sourceRow.key) ?? {
                key: `source-preview-${sourceRow.key}`,
                id: null,
                type: 'source',
                source_key: sourceRow.key,
                medium: '',
                medium_name: '',
                campaign: '',
                campaign_name: '',
                phone_key: sourceRow.default_phone_key || '',
                open_booking_widget: !! sourceRow.open_booking_widget,
                is_organic: false,
                is_organic_overridden: false,
                effective_is_organic: !! sourceRow.is_organic,
                created_at: '',
                started_at: '',
                stopped_at: '',
                archived_at: '',
                restarted_from_id: null,
            }
        },
        setSourcePhone(sourceNode, phoneKey) {
            if (! sourceNode?.source) {
                return
            }

            const sourceKey = sourceNode.key
            const value = String(phoneKey || '')

            sourceNode.source.phone_key = value
            sourceNode.source.default_phone_key = value

            this.state.sources = this.state.sources.map((row) => {
                if (row.key !== sourceKey) {
                    return row
                }

                return {
                    ...row,
                    phone_key: value,
                    default_phone_key: value,
                }
            })

            if (! value) {
                this.state.campaigns = this.state.campaigns.filter((row) => ! (row.type === 'source' && row.source_key === sourceKey))
                sourceNode.sourceRule = null
                this.syncState()

                return
            }

            this.state.campaigns = this.state.campaigns.map((row) => {
                if (row.type !== 'source' || row.source_key !== sourceKey) {
                    return row
                }

                return {
                    ...row,
                    phone_key: value,
                    open_booking_widget: !! sourceNode.source.open_booking_widget,
                }
            })

            if (sourceNode.sourceRule) {
                sourceNode.sourceRule.phone_key = value
            }

            this.syncState()
        },
        toggleCampaignSort(mode, column) {
            const target = mode === 'archive' ? this.archiveSort : this.trackingSort

            if (target.column === column) {
                target.direction = target.direction === 'asc' ? 'desc' : 'asc'

                return
            }

            target.column = column
            target.direction = column === 'created_at' ? 'desc' : 'asc'
        },
        campaignSortIsActive(mode, column) {
            const target = mode === 'archive' ? this.archiveSort : this.trackingSort

            return target.column === column
        },
        campaignSortIndicator(mode, column) {
            const target = mode === 'archive' ? this.archiveSort : this.trackingSort

            if (target.column !== column) {
                return ''
            }

            return target.direction === 'asc' ? '↑' : '↓'
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
            return this.campaignRowsForView('tracking').map((row) => row.key)
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
                is_organic: false,
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
                        campaign: '',
                        campaign_name: '',
                        phone_key: defaultPhoneKey,
                        open_booking_widget: !! sourceRow.open_booking_widget,
                        is_organic: false,
                        is_organic_overridden: false,
                        effective_is_organic: !! sourceRow.is_organic,
                        created_at: this.currentDateTimeValue(),
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
                                open_booking_widget: !! sourceRow.open_booking_widget,
                                effective_is_organic: row.is_organic_overridden ? !! row.is_organic : !! sourceRow.is_organic,
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
                .filter((row) => ['medium', 'campaign'].includes(row.type) && row.phone_key)
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
            this.addMediumForSource(this.state.sources[0]?.key ?? '')
        },
        addMediumForSource(sourceKey) {
            this.state.campaigns.unshift({
                key: this.makeKey('campaign'),
                id: null,
                type: 'medium',
                source_key: sourceKey,
                medium: '',
                medium_name: '',
                campaign: '',
                campaign_name: '',
                phone_key: '',
                open_booking_widget: false,
                is_organic: false,
                is_organic_overridden: false,
                effective_is_organic: false,
                created_at: this.currentDateTimeValue(),
                started_at: this.currentDateTimeValue(),
                stopped_at: '',
                archived_at: '',
                restarted_from_id: null,
            })

            this.activeTab = 'tracking'
            if (sourceKey) {
                this.expandedSources.tracking = Array.from(new Set([...this.expandedSources.tracking, sourceKey]))
            }
            this.syncState()
        },
        addCampaignForMedium(sourceKey, medium) {
            const mediumValue = String(medium || '').trim()

            this.state.campaigns.unshift({
                key: this.makeKey('campaign'),
                id: null,
                type: 'campaign',
                source_key: sourceKey,
                medium: mediumValue,
                medium_name: '',
                campaign: '',
                campaign_name: '',
                phone_key: '',
                open_booking_widget: false,
                is_organic: false,
                is_organic_overridden: false,
                effective_is_organic: false,
                created_at: this.currentDateTimeValue(),
                started_at: this.currentDateTimeValue(),
                stopped_at: '',
                archived_at: '',
                restarted_from_id: null,
            })

            this.activeTab = 'tracking'
            this.expandedSources.tracking = Array.from(new Set([...this.expandedSources.tracking, sourceKey]))
            this.expandedMediums.tracking = Array.from(new Set([...this.expandedMediums.tracking, this.mediumTreeKey(sourceKey, mediumValue)]))
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

            if (row.type === 'campaign') {
                return `${this.sourceLabel(row.source_key)} / ${row.medium || '—'} / ${row.campaign || '—'}`
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

                if (archivedCampaign.type === 'campaign') {
                    return `Архив: ${this.sourceLabel(archivedCampaign.source_key)} / ${archivedCampaign.medium || '—'} / ${archivedCampaign.campaign || '—'}`
                }

                return `Архив: ${this.sourceLabel(archivedCampaign.source_key)} / ${archivedCampaign.medium || '—'}`
            }

            return 'Свободен'
        },
    }"
    class="w-full rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900"
>
    <style>
        :root {
            --utm-text-strong: #111827;
            --utm-text-muted: #6b7280;
        }

        .dark {
            --utm-text-strong: #f3f4f6;
            --utm-text-muted: #d1d5db;
        }
    </style>

    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-white/10">
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                x-bind:class="activeTab === 'tracking' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-200'"
                x-on:click="activeTab = 'tracking'"
            >
                Кампании
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
            <div class="space-y-3">
                <div class="flex items-end gap-2">
                    <label class="block flex-1">
                        <span class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">Поиск</span>
                        <input
                            type="text"
                            class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                            x-model="trackingSearch"
                            placeholder="Source, medium, campaign, телефон, название"
                        />
                    </label>

                    <button
                        type="button"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-300 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                        x-bind:class="trackingFiltersOpen ? 'bg-gray-100 dark:bg-white/10' : ''"
                        x-on:click="trackingFiltersOpen = !trackingFiltersOpen"
                        title="Фильтры"
                        aria-label="Фильтры"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M3.25 4A.75.75 0 0 1 4 3.25h12a.75.75 0 0 1 .53 1.28L12 9.06v5.19a.75.75 0 0 1-1.14.64l-2-1.25a.75.75 0 0 1-.36-.64V9.06L3.47 4.53A.75.75 0 0 1 3.25 4Z" />
                        </svg>
                    </button>
                </div>

                <div x-show="trackingFiltersOpen" x-cloak class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <label class="block">
                        <span class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">Source</span>
                        <select
                            class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                            x-model="trackingFilters.source_key"
                        >
                            <option value="">Все source</option>
                            <template x-for="sourceRow in sourceOptions()" :key="`tracking-filter-source-${sourceRow.key}`">
                                <option x-bind:value="sourceRow.key" x-text="sourceRow.name ? `${sourceRow.source} (${sourceRow.name})` : sourceRow.source"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">Телефон</span>
                        <select
                            class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                            x-model="trackingFilters.phone_key"
                        >
                            <option value="">Все телефоны</option>
                            <template x-for="phoneRow in state.phones" :key="`tracking-filter-phone-${phoneRow.key}`">
                                <option x-bind:value="phoneRow.key" x-text="phoneRow.phone"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">Запуск</span>
                        <input
                            type="date"
                            class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                            x-model="trackingFilters.started_date"
                        />
                    </label>

                </div>
            </div>

            <div class="space-y-2">
                <template x-if="treeForView('tracking').length === 0">
                    <div class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                        Ничего не найдено по текущим фильтрам.
                    </div>
                </template>

                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
                    <div style="min-width: 1524px;">
                        <div class="utm-tracker-grid utm-tracker-grid--active border-b border-gray-200 bg-gray-50 px-3 py-2 text-[11px] font-medium uppercase text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                            <span></span>
                            <span>UTM</span>
                            <span>Название</span>
                            <span>Телефон</span>
                            <span>Виджет</span>
                            <span>Органика</span>
                            <span>Ссылка</span>
                            <span>Запуск</span>
                            <span></span>
                        </div>

                        <template x-for="sourceNode in treeForView('tracking')" :key="`tracking-source-${sourceNode.key}`">
                            <div>
                                <div class="utm-tracker-grid utm-tracker-grid--active border-b border-gray-100 px-3 py-2 dark:border-white/10">
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5" x-on:click="toggleSource('tracking', sourceNode.key)" x-bind:title="isSourceExpanded('tracking', sourceNode.key) ? 'Свернуть source' : 'Раскрыть source'">
                                        <svg class="h-4 w-4 transition" x-bind:class="isSourceExpanded('tracking', sourceNode.key) ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                                    </button>

                                    <div class="utm-tracker-cell">
                                        <div class="truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="sourceNode.source?.source || 'Без source'"></div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400" x-text="`${sourceNode.mediums.length} medium`"></div>
                                    </div>

                                    <div class="utm-tracker-cell truncate text-xs text-gray-700 dark:text-gray-200" x-text="sourceNode.source?.name || '—'"></div>

                                    <div class="flex items-center gap-2">
                                        <select class="block min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-model="sourceNode.source.phone_key" x-bind:disabled="isPersisting || ! sourceNode.source" x-on:change="setSourcePhone(sourceNode, sourceNode.source.phone_key)">
                                            <option value="">Без телефона</option>
                                            <template x-for="phoneRow in state.phones" :key="phoneRow.key">
                                                <option x-bind:disabled="isSourcePhoneOptionDisabled(phoneRow.key, sourceNode.source)" x-bind:selected="sourceNode.source.phone_key === phoneRow.key" x-bind:value="phoneRow.key" x-text="sourcePhoneOptionLabel(phoneRow.key, sourceNode.source)"></option>
                                            </template>
                                        </select>
                                        <button type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-40" x-bind:class="isPhoneCopied(`tree-source-${sourceNode.key}`) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200' : ''" x-bind:disabled="isPersisting || ! sourceNode.source?.phone_key" x-on:click="copySelectedPhone(sourceNode.source.phone_key, `tree-source-${sourceNode.key}`)" title="Скопировать телефон" aria-label="Скопировать телефон"><svg x-show="! isPhoneCopied(`tree-source-${sourceNode.key}`)" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 3.5A2.5 2.5 0 0 1 9.5 1h5A2.5 2.5 0 0 1 17 3.5v8A2.5 2.5 0 0 1 14.5 14h-5A2.5 2.5 0 0 1 7 11.5v-8Z" /><path d="M4.5 6A2.5 2.5 0 0 0 2 8.5v8A2.5 2.5 0 0 0 4.5 19h5a2.5 2.5 0 0 0 2.45-2H4.5a.5.5 0 0 1-.5-.5v-8a.5.5 0 0 1 .5-.5V6Z" /></svg><svg x-show="isPhoneCopied(`tree-source-${sourceNode.key}`)" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z" clip-rule="evenodd" /></svg></button>
                                    </div>

                                    <input type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent" x-model="sourceNode.source.open_booking_widget" x-bind:disabled="isPersisting || ! sourceNode.source" x-on:change="if (sourceNode.sourceRule) sourceNode.sourceRule.open_booking_widget = sourceNode.source.open_booking_widget; syncState()" />
                                    <input type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent" x-model="sourceNode.source.is_organic" x-bind:disabled="isPersisting || ! sourceNode.source" x-on:change="syncState()" />
                                    <input type="text" readonly class="block w-full cursor-copy rounded-md border-gray-300 bg-gray-50 px-2 py-1.5 text-xs shadow-none transition dark:border-white/10 dark:bg-white/5 dark:text-white" x-bind:value="trackingLinkValue(sourceFallbackRow(sourceNode.source))" x-bind:class="isTrackingLinkCopied(sourceFallbackRow(sourceNode.source)) ? 'border-emerald-400 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-300 dark:border-emerald-500/60 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30' : ''" x-on:click="copyTrackingLink(sourceFallbackRow(sourceNode.source))" />
                                    <span class="text-xs text-gray-700 dark:text-gray-100" x-text="formatDateTime(sourceNode.sourceRule?.started_at)"></span>

                                    <div class="utm-tracker-actions">
                                        <button type="button" class="utm-tracker-action-link text-xs font-medium text-primary-600 hover:underline dark:text-primary-400 disabled:cursor-not-allowed disabled:opacity-60" x-bind:disabled="isPersisting || ! sourceNode.source" x-on:click="addMediumForSource(sourceNode.key)">+ medium</button>
                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="sourceNode.sourceRule ? '' : 'invisible'" x-bind:disabled="isPersisting || ! sourceNode.sourceRule" x-on:click="sourceNode.sourceRule && requestStopCampaign(sourceNode.sourceRule.key)" title="Остановить" aria-label="Остановить"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#374151" aria-hidden="true"><path d="M6 6.75A.75.75 0 0 1 6.75 6h6.5a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-.75.75h-6.5A.75.75 0 0 1 6 13.25v-6.5Z" /></svg></button>
                                    </div>
                                </div>

                                <div x-show="isSourceExpanded('tracking', sourceNode.key)">
                                    <template x-for="mediumNode in sourceNode.mediums" :key="`tracking-medium-${sourceNode.key}-${mediumNode.key}`">
                                        <div>
                                            <div class="utm-tracker-grid utm-tracker-grid--active border-b border-gray-100 bg-gray-50/70 px-3 py-2 dark:border-white/10 dark:bg-white/[0.03]">
                                                <button type="button" class="ml-4 inline-flex h-7 w-7 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-white dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5" x-on:click="toggleMedium('tracking', sourceNode.key, mediumNode.medium)" x-bind:title="isMediumExpanded('tracking', sourceNode.key, mediumNode.medium) ? 'Свернуть medium' : 'Раскрыть medium'"><svg class="h-4 w-4 transition" x-bind:class="isMediumExpanded('tracking', sourceNode.key, mediumNode.medium) ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg></button>
                                                <input x-show="mediumNode.mediumRow" type="text" class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-model="mediumNode.mediumRow.medium" x-bind:disabled="isPersisting" x-on:input.debounce.300ms="syncState()" placeholder="utm_medium" />
                                                <span x-show="! mediumNode.mediumRow" class="truncate text-xs text-gray-800 dark:text-gray-100" x-text="mediumNode.medium || '—'"></span>
                                                <input x-show="mediumNode.mediumRow" type="text" class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-model="mediumNode.mediumRow.medium_name" x-bind:disabled="isPersisting" x-on:input.debounce.300ms="syncState()" placeholder="Название" />
                                                <span x-show="! mediumNode.mediumRow" class="text-xs text-gray-400">—</span>

                                                <div class="flex items-center gap-2">
                                                    <select x-show="mediumNode.mediumRow" class="block min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-bind:class="isDuplicateCampaignPhone(mediumNode.mediumRow) ? 'border-rose-500 bg-rose-50 text-rose-700 ring-1 ring-rose-400 focus:border-rose-500 focus:ring-rose-500 dark:border-rose-500/70 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/40' : ''" x-model="mediumNode.mediumRow.phone_key" x-bind:disabled="isPersisting" x-on:change="syncState()"><option value="">Без телефона</option><template x-for="phoneRow in state.phones" :key="phoneRow.key"><option x-bind:disabled="isPhoneOptionDisabled(phoneRow.key, mediumNode.mediumRow)" x-bind:selected="mediumNode.mediumRow.phone_key === phoneRow.key" x-bind:value="phoneRow.key" x-text="phoneOptionLabel(phoneRow.key, mediumNode.mediumRow)"></option></template></select>
                                                    <span x-show="! mediumNode.mediumRow" class="text-xs text-gray-400">—</span>
                                                    <button type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-white dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40" x-show="mediumNode.mediumRow" x-bind:class="isPhoneCopied(`tree-${mediumNode.mediumRow?.key}`) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200' : ''" x-bind:disabled="isPersisting || ! mediumNode.mediumRow?.phone_key" x-on:click="copySelectedPhone(mediumNode.mediumRow.phone_key, `tree-${mediumNode.mediumRow.key}`)" title="Скопировать телефон" aria-label="Скопировать телефон"><svg x-show="! isPhoneCopied(`tree-${mediumNode.mediumRow?.key}`)" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 3.5A2.5 2.5 0 0 1 9.5 1h5A2.5 2.5 0 0 1 17 3.5v8A2.5 2.5 0 0 1 14.5 14h-5A2.5 2.5 0 0 1 7 11.5v-8Z" /><path d="M4.5 6A2.5 2.5 0 0 0 2 8.5v8A2.5 2.5 0 0 0 4.5 19h5a2.5 2.5 0 0 0 2.45-2H4.5a.5.5 0 0 1-.5-.5v-8a.5.5 0 0 1 .5-.5V6Z" /></svg><svg x-show="isPhoneCopied(`tree-${mediumNode.mediumRow?.key}`)" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z" clip-rule="evenodd" /></svg></button>
                                                </div>

                                                <input x-show="mediumNode.mediumRow" type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent" x-model="mediumNode.mediumRow.open_booking_widget" x-bind:disabled="isPersisting" x-on:change="syncState()" />
                                                <input x-show="mediumNode.mediumRow" type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent" x-bind:checked="campaignEffectiveIsOrganic(mediumNode.mediumRow)" x-bind:disabled="isPersisting" x-on:change="setCampaignOrganic(mediumNode.mediumRow, $event.target.checked)" />
                                                <input x-show="mediumNode.mediumRow" type="text" readonly class="block w-full cursor-copy rounded-md border-gray-300 bg-white px-2 py-1.5 text-xs shadow-none transition dark:border-white/10 dark:bg-white/5 dark:text-white" x-bind:value="trackingLinkValue(mediumNode.mediumRow)" x-bind:class="isTrackingLinkCopied(mediumNode.mediumRow) ? 'border-emerald-400 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-300 dark:border-emerald-500/60 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30' : ''" x-on:click="copyTrackingLink(mediumNode.mediumRow)" />
                                                <span x-show="! mediumNode.mediumRow" class="text-xs text-gray-400">—</span>
                                                <span class="text-xs text-gray-700 dark:text-gray-100" x-text="formatDateTime(mediumNode.mediumRow?.started_at)"></span>

                                                <div class="utm-tracker-actions">
                                                    <button type="button" class="utm-tracker-action-link text-xs font-medium text-primary-600 hover:underline dark:text-primary-400 disabled:cursor-not-allowed disabled:opacity-60" x-bind:disabled="isPersisting || ! mediumNode.medium" x-on:click="addCampaignForMedium(sourceNode.key, mediumNode.medium)">+ campaign</button>
                                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-white dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="mediumNode.mediumRow ? '' : 'invisible'" x-bind:disabled="isPersisting || ! mediumNode.mediumRow" x-on:click="mediumNode.mediumRow && requestStopCampaign(mediumNode.mediumRow.key)" title="Остановить" aria-label="Остановить"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#374151" aria-hidden="true"><path d="M6 6.75A.75.75 0 0 1 6.75 6h6.5a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-.75.75h-6.5A.75.75 0 0 1 6 13.25v-6.5Z" /></svg></button>
                                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="mediumNode.mediumRow ? '' : 'invisible'" x-bind:disabled="isPersisting || ! mediumNode.mediumRow" x-on:click="mediumNode.mediumRow && requestDeleteCampaign(mediumNode.mediumRow.key)" title="Удалить в архив" aria-label="Удалить в архив"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" /></svg></button>
                                                </div>
                                            </div>

                                            <div x-show="isMediumExpanded('tracking', sourceNode.key, mediumNode.medium)">
                                                <template x-for="campaignRow in mediumNode.campaigns" :key="campaignRow.key">
                                                    <div class="utm-tracker-grid utm-tracker-grid--active border-b border-gray-100 px-3 py-2 dark:border-white/10" x-bind:class="isDuplicateCampaignPhone(campaignRow) ? 'bg-rose-50 dark:bg-rose-500/10' : 'bg-white dark:bg-gray-900'">
                                                        <span></span>
                                                        <input type="text" class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-model="campaignRow.campaign" x-bind:disabled="isPersisting" x-on:input.debounce.300ms="syncState()" placeholder="utm_campaign" />
                                                        <input type="text" class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-model="campaignRow.campaign_name" x-bind:disabled="isPersisting" x-on:input.debounce.300ms="syncState()" placeholder="Название" />
                                                        <div class="flex items-center gap-2"><select class="block min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-bind:class="isDuplicateCampaignPhone(campaignRow) ? 'border-rose-500 bg-rose-50 text-rose-700 ring-1 ring-rose-400 focus:border-rose-500 focus:ring-rose-500 dark:border-rose-500/70 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/40' : ''" x-model="campaignRow.phone_key" x-bind:disabled="isPersisting" x-on:change="syncState()"><option value="">Без телефона</option><template x-for="phoneRow in state.phones" :key="phoneRow.key"><option x-bind:disabled="isPhoneOptionDisabled(phoneRow.key, campaignRow)" x-bind:selected="campaignRow.phone_key === phoneRow.key" x-bind:value="phoneRow.key" x-text="phoneOptionLabel(phoneRow.key, campaignRow)"></option></template></select><button type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-40" x-bind:class="isPhoneCopied(`tree-${campaignRow.key}`) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200' : ''" x-bind:disabled="isPersisting || ! campaignRow.phone_key" x-on:click="copySelectedPhone(campaignRow.phone_key, `tree-${campaignRow.key}`)" title="Скопировать телефон" aria-label="Скопировать телефон"><svg x-show="! isPhoneCopied(`tree-${campaignRow.key}`)" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 3.5A2.5 2.5 0 0 1 9.5 1h5A2.5 2.5 0 0 1 17 3.5v8A2.5 2.5 0 0 1 14.5 14h-5A2.5 2.5 0 0 1 7 11.5v-8Z" /><path d="M4.5 6A2.5 2.5 0 0 0 2 8.5v8A2.5 2.5 0 0 0 4.5 19h5a2.5 2.5 0 0 0 2.45-2H4.5a.5.5 0 0 1-.5-.5v-8a.5.5 0 0 1 .5-.5V6Z" /></svg><svg x-show="isPhoneCopied(`tree-${campaignRow.key}`)" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z" clip-rule="evenodd" /></svg></button></div>
                                                        <input type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent" x-model="campaignRow.open_booking_widget" x-bind:disabled="isPersisting" x-on:change="syncState()" />
                                                        <span class="text-xs text-gray-400">—</span>
                                                        <input type="text" readonly class="block w-full cursor-copy rounded-md border-gray-300 bg-gray-50 px-2 py-1.5 text-xs shadow-none transition dark:border-white/10 dark:bg-white/5 dark:text-white" x-bind:value="trackingLinkValue(campaignRow)" x-bind:class="isTrackingLinkCopied(campaignRow) ? 'border-emerald-400 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-300 dark:border-emerald-500/60 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30' : ''" x-on:click="copyTrackingLink(campaignRow)" />
                                                        <span class="text-xs text-gray-700 dark:text-gray-100" x-text="formatDateTime(campaignRow.started_at)"></span>
                                                        <div class="utm-tracker-actions"><button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-60" x-bind:disabled="isPersisting" x-on:click="requestStopCampaign(campaignRow.key)" title="Остановить" aria-label="Остановить"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#374151" aria-hidden="true"><path d="M6 6.75A.75.75 0 0 1 6.75 6h6.5a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-.75.75h-6.5A.75.75 0 0 1 6 13.25v-6.5Z" /></svg></button><button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:disabled="isPersisting" x-on:click="requestDeleteCampaign(campaignRow.key)" title="Удалить в архив" aria-label="Удалить в архив"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" /></svg></button></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'archive'" x-cloak class="space-y-3">
            <div class="space-y-3">
                <div class="flex items-end gap-2">
                    <label class="block flex-1">
                        <span class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">Поиск</span>
                        <input
                            type="text"
                            class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                            x-model="archiveSearch"
                            placeholder="Source, medium, campaign, телефон, название"
                        />
                    </label>

                    <button
                        type="button"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-300 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                        x-bind:class="archiveFiltersOpen ? 'bg-gray-100 dark:bg-white/10' : ''"
                        x-on:click="archiveFiltersOpen = !archiveFiltersOpen"
                        title="Фильтры"
                        aria-label="Фильтры"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M3.25 4A.75.75 0 0 1 4 3.25h12a.75.75 0 0 1 .53 1.28L12 9.06v5.19a.75.75 0 0 1-1.14.64l-2-1.25a.75.75 0 0 1-.36-.64V9.06L3.47 4.53A.75.75 0 0 1 3.25 4Z" />
                        </svg>
                    </button>
                </div>

                <div x-show="archiveFiltersOpen" x-cloak class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <label class="block">
                        <span class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">Source</span>
                        <select
                            class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                            x-model="archiveFilters.source_key"
                        >
                            <option value="">Все source</option>
                            <template x-for="sourceRow in sourceOptions()" :key="`archive-filter-source-${sourceRow.key}`">
                                <option x-bind:value="sourceRow.key" x-text="sourceRow.name ? `${sourceRow.source} (${sourceRow.name})` : sourceRow.source"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">Телефон</span>
                        <select
                            class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                            x-model="archiveFilters.phone_key"
                        >
                            <option value="">Все телефоны</option>
                            <template x-for="phoneRow in state.phones" :key="`archive-filter-phone-${phoneRow.key}`">
                                <option x-bind:value="phoneRow.key" x-text="phoneRow.phone"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">Запуск</span>
                        <input
                            type="date"
                            class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                            x-model="archiveFilters.started_date"
                        />
                    </label>

                </div>
            </div>

            <div class="space-y-2">
                <template x-if="treeForView('archive').length === 0">
                    <div class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                        Ничего не найдено по текущим фильтрам.
                    </div>
                </template>

                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
                    <div style="min-width: 1420px;">
                        <div class="utm-tracker-grid utm-tracker-grid--archive border-b border-gray-200 bg-gray-50 px-3 py-2 text-[11px] font-medium uppercase text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                            <span></span>
                            <span>UTM</span>
                            <span>Название</span>
                            <span>Телефон</span>
                            <span>Виджет</span>
                            <span>Ссылка</span>
                            <span>Запуск</span>
                            <span>Остановка</span>
                            <span></span>
                        </div>

                        <template x-for="sourceNode in treeForView('archive')" :key="`archive-source-${sourceNode.key}`">
                            <div>
                                <div class="utm-tracker-grid utm-tracker-grid--archive border-b border-gray-100 px-3 py-2 dark:border-white/10">
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5" x-on:click="toggleSource('archive', sourceNode.key)" x-bind:title="isSourceExpanded('archive', sourceNode.key) ? 'Свернуть source' : 'Раскрыть source'"><svg class="h-4 w-4 transition" x-bind:class="isSourceExpanded('archive', sourceNode.key) ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg></button>
                                    <div class="min-w-0"><div class="truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="sourceNode.source?.source || 'Без source'"></div><div class="text-[11px] text-gray-500 dark:text-gray-400" x-text="`${sourceNode.mediums.length + (sourceNode.sourceRule ? 1 : 0)} архив`"></div></div>
                                    <span class="truncate text-xs text-gray-700 dark:text-gray-200" x-text="sourceNode.source?.name || '—'"></span>
                                    <span class="truncate text-xs text-gray-800 dark:text-gray-100" x-text="sourceNode.sourceRule ? phoneLabel(sourceNode.sourceRule.phone_key) : '—'"></span>
                                    <span class="text-xs text-gray-800 dark:text-gray-100" x-text="sourceNode.sourceRule ? (sourceNode.sourceRule.open_booking_widget ? 'Да' : 'Нет') : '—'"></span>
                                    <input x-show="sourceNode.sourceRule" type="text" readonly class="block w-full cursor-copy rounded-md border-gray-300 bg-gray-50 px-2 py-1.5 text-xs shadow-none transition dark:border-white/10 dark:bg-white/5 dark:text-white" x-bind:value="trackingLinkValue(sourceNode.sourceRule)" x-bind:class="isTrackingLinkCopied(sourceNode.sourceRule) ? 'border-emerald-400 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-300 dark:border-emerald-500/60 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30' : ''" x-on:click="copyTrackingLink(sourceNode.sourceRule)" />
                                    <span x-show="! sourceNode.sourceRule" class="text-xs text-gray-400">—</span>
                                    <span class="text-xs text-gray-700 dark:text-gray-100" x-text="formatDateTime(sourceNode.sourceRule?.started_at)"></span>
                                    <span class="text-xs text-gray-700 dark:text-gray-100" x-text="formatDateTime(sourceNode.sourceRule?.stopped_at || sourceNode.sourceRule?.archived_at)"></span>
                                    <div class="utm-tracker-actions">
                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-emerald-200 text-emerald-600 transition hover:bg-emerald-50 dark:border-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="sourceNode.sourceRule ? '' : 'invisible'" x-bind:disabled="isPersisting || ! sourceNode.sourceRule" x-on:click="sourceNode.sourceRule && requestResumeCampaign(sourceNode.sourceRule.key)" title="Возобновить" aria-label="Возобновить"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M6.22 5.22a.75.75 0 0 1 1.06 0l6 4.25a.75.75 0 0 1 0 1.22l-6 4.25A.75.75 0 0 1 6 14.31V5.75a.75.75 0 0 1 .22-.53Z" clip-rule="evenodd" /></svg></button>
                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="sourceNode.sourceRule ? '' : 'invisible'" x-bind:disabled="isPersisting || ! sourceNode.sourceRule" x-on:click="sourceNode.sourceRule && requestDeleteArchivedCampaign(sourceNode.sourceRule.key)" title="Удалить" aria-label="Удалить"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" /></svg></button>
                                    </div>
                                </div>

                                <div x-show="isSourceExpanded('archive', sourceNode.key)">
                                    <template x-for="mediumNode in sourceNode.mediums" :key="`archive-medium-${sourceNode.key}-${mediumNode.key}`">
                                        <div>
                                            <div class="utm-tracker-grid utm-tracker-grid--archive border-b border-gray-100 bg-gray-50/70 px-3 py-2 dark:border-white/10 dark:bg-white/[0.03]">
                                                <button type="button" class="ml-4 inline-flex h-7 w-7 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-white dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5" x-on:click="toggleMedium('archive', sourceNode.key, mediumNode.medium)" x-bind:title="isMediumExpanded('archive', sourceNode.key, mediumNode.medium) ? 'Свернуть medium' : 'Раскрыть medium'"><svg class="h-4 w-4 transition" x-bind:class="isMediumExpanded('archive', sourceNode.key, mediumNode.medium) ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg></button>
                                                <span class="truncate text-xs font-medium text-gray-900 dark:text-white" x-text="mediumNode.medium || '—'"></span>
                                                <span class="truncate text-xs text-gray-700 dark:text-gray-200" x-text="mediumNode.mediumRow ? campaignName(mediumNode.mediumRow) : '—'"></span>
                                                <span class="truncate text-xs text-gray-800 dark:text-gray-100" x-text="mediumNode.mediumRow ? phoneLabel(mediumNode.mediumRow.phone_key) : '—'"></span>
                                                <span class="text-xs text-gray-800 dark:text-gray-100" x-text="mediumNode.mediumRow ? (mediumNode.mediumRow.open_booking_widget ? 'Да' : 'Нет') : '—'"></span>
                                                <input x-show="mediumNode.mediumRow" type="text" readonly class="block w-full cursor-copy rounded-md border-gray-300 bg-white px-2 py-1.5 text-xs shadow-none transition dark:border-white/10 dark:bg-white/5 dark:text-white" x-bind:value="trackingLinkValue(mediumNode.mediumRow)" x-bind:class="isTrackingLinkCopied(mediumNode.mediumRow) ? 'border-emerald-400 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-300 dark:border-emerald-500/60 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30' : ''" x-on:click="copyTrackingLink(mediumNode.mediumRow)" />
                                                <span x-show="! mediumNode.mediumRow" class="text-xs text-gray-400">—</span>
                                                <span class="text-xs text-gray-700 dark:text-gray-100" x-text="formatDateTime(mediumNode.mediumRow?.started_at)"></span>
                                                <span class="text-xs text-gray-700 dark:text-gray-100" x-text="formatDateTime(mediumNode.mediumRow?.stopped_at || mediumNode.mediumRow?.archived_at)"></span>
                                                <div class="utm-tracker-actions">
                                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-emerald-200 text-emerald-600 transition hover:bg-emerald-50 dark:border-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="mediumNode.mediumRow ? '' : 'invisible'" x-bind:disabled="isPersisting || ! mediumNode.mediumRow" x-on:click="mediumNode.mediumRow && requestResumeCampaign(mediumNode.mediumRow.key)" title="Возобновить" aria-label="Возобновить"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M6.22 5.22a.75.75 0 0 1 1.06 0l6 4.25a.75.75 0 0 1 0 1.22l-6 4.25A.75.75 0 0 1 6 14.31V5.75a.75.75 0 0 1 .22-.53Z" clip-rule="evenodd" /></svg></button>
                                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="mediumNode.mediumRow ? '' : 'invisible'" x-bind:disabled="isPersisting || ! mediumNode.mediumRow" x-on:click="mediumNode.mediumRow && requestDeleteArchivedCampaign(mediumNode.mediumRow.key)" title="Удалить" aria-label="Удалить"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" /></svg></button>
                                                </div>
                                            </div>

                                            <div x-show="isMediumExpanded('archive', sourceNode.key, mediumNode.medium)">
                                                <template x-for="campaignRow in mediumNode.campaigns" :key="campaignRow.key">
                                                    <div class="utm-tracker-grid utm-tracker-grid--archive border-b border-gray-100 px-3 py-2 dark:border-white/10">
                                                        <span></span>
                                                        <span class="truncate text-xs font-medium text-gray-900 dark:text-white" x-text="campaignRow.campaign || '—'"></span>
                                                        <span class="truncate text-xs text-gray-700 dark:text-gray-200" x-text="campaignName(campaignRow)"></span>
                                                        <span class="truncate text-xs text-gray-800 dark:text-gray-100" x-text="phoneLabel(campaignRow.phone_key)"></span>
                                                        <span class="text-xs text-gray-800 dark:text-gray-100" x-text="campaignRow.open_booking_widget ? 'Да' : 'Нет'"></span>
                                                        <input type="text" readonly class="block w-full cursor-copy rounded-md border-gray-300 bg-gray-50 px-2 py-1.5 text-xs shadow-none transition dark:border-white/10 dark:bg-white/5 dark:text-white" x-bind:value="trackingLinkValue(campaignRow)" x-bind:class="isTrackingLinkCopied(campaignRow) ? 'border-emerald-400 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-300 dark:border-emerald-500/60 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30' : ''" x-on:click="copyTrackingLink(campaignRow)" />
                                                        <span class="text-xs text-gray-700 dark:text-gray-100" x-text="formatDateTime(campaignRow.started_at)"></span>
                                                        <span class="text-xs text-gray-700 dark:text-gray-100" x-text="formatDateTime(campaignRow.stopped_at || campaignRow.archived_at)"></span>
                                                        <div class="utm-tracker-actions">
                                                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-emerald-200 text-emerald-600 transition hover:bg-emerald-50 dark:border-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:disabled="isPersisting" x-on:click="requestResumeCampaign(campaignRow.key)" title="Возобновить" aria-label="Возобновить"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M6.22 5.22a.75.75 0 0 1 1.06 0l6 4.25a.75.75 0 0 1 0 1.22l-6 4.25A.75.75 0 0 1 6 14.31V5.75a.75.75 0 0 1 .22-.53Z" clip-rule="evenodd" /></svg></button>
                                                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:disabled="isPersisting" x-on:click="requestDeleteArchivedCampaign(campaignRow.key)" title="Удалить" aria-label="Удалить"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" /></svg></button>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
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
                            <th class="w-[22%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Source</th>
                            <th class="w-[24%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Название</th>
                            <th class="w-[16%] py-2 pr-3 text-center font-medium text-gray-500 dark:text-gray-400">Органический источник</th>
                            <th class="py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Дефолтный телефон</th>
                            <th class="w-24 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Действия</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-if="state.sources.length === 0">
                            <tr>
                                <td colspan="5" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
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

                                <td class="py-2 pr-3 align-top text-center">
                                    <label class="inline-flex h-8 items-center justify-center">
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent"
                                            x-model="sourceRow.is_organic"
                                            x-bind:disabled="isPersisting"
                                            x-on:change="syncState()"
                                        />
                                    </label>
                                </td>

                                <td class="py-2 pr-3 align-top">
                                    <div class="flex items-start gap-2">
                                        <select
                                            class="block min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
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

                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-40"
                                            x-bind:class="isPhoneCopied(`source-${sourceRow.key}`) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200' : ''"
                                            x-bind:disabled="isPersisting || ! sourceRow.default_phone_key"
                                            x-bind:title="isPhoneCopied(`source-${sourceRow.key}`) ? 'Скопировано' : 'Скопировать телефон'"
                                            x-bind:aria-label="isPhoneCopied(`source-${sourceRow.key}`) ? 'Скопировано' : 'Скопировать телефон'"
                                            x-on:click="copySelectedPhone(sourceRow.default_phone_key, `source-${sourceRow.key}`)"
                                        >
                                            <svg x-show="! isPhoneCopied(`source-${sourceRow.key}`)" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M7 3.5A2.5 2.5 0 0 1 9.5 1h5A2.5 2.5 0 0 1 17 3.5v8A2.5 2.5 0 0 1 14.5 14h-5A2.5 2.5 0 0 1 7 11.5v-8Z" />
                                                <path d="M4.5 6A2.5 2.5 0 0 0 2 8.5v8A2.5 2.5 0 0 0 4.5 19h5a2.5 2.5 0 0 0 2.45-2H4.5a.5.5 0 0 1-.5-.5v-8a.5.5 0 0 1 .5-.5V6Z" />
                                            </svg>
                                            <svg x-show="isPhoneCopied(`source-${sourceRow.key}`)" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
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
                                    class="py-2 pr-3 align-top text-gray-600 dark:text-gray-100"
                                    x-bind:class="isDuplicatePhoneInActiveCampaigns(phoneRow.key) ? 'font-medium' : ''"
                                    x-bind:style="isDuplicatePhoneInActiveCampaigns(phoneRow.key) ? 'font-size:10px; line-height:12px; color:#dc2626;' : 'color:var(--utm-text-strong) !important;'"
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
