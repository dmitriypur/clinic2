{
        state: $wire.entangle('state'),
        activeTab: 'tracking',
        isSyncing: false,
        isPersisting: false,
        trackingBaseUrl: @js($trackingBaseUrl),
        trackingCitySlug: @js($trackingCitySlug),
        vkMiniAppId: @js($vkMiniAppId),
        cabinetOptions: @js($cabinetOptions),
        copiedTrackingKey: null,
        copiedPhoneContext: null,
        copyResetTimer: null,
        phoneCopyResetTimer: null,
        toast: {
            visible: false,
            title: '',
            message: '',
            tone: 'success',
        },
        toastTimer: null,
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
        normalizeCabinet(value) {
            const cabinet = String(value || '')

            return Object.prototype.hasOwnProperty.call(this.cabinetOptions, cabinet) ? cabinet : ''
        },
        cabinetLabel(value) {
            const cabinet = this.normalizeCabinet(value)

            return cabinet ? this.cabinetOptions[cabinet] : '—'
        },
        showToast(title, message = '', tone = 'success') {
            if (this.toastTimer) {
                clearTimeout(this.toastTimer)
            }

            this.toast = {
                visible: true,
                title,
                message,
                tone,
            }

            this.toastTimer = setTimeout(() => {
                this.toast.visible = false
            }, 2600)
        },
        shortToastText(value, maxLength = 96) {
            const text = String(value || '').trim()

            return text.length > maxLength ? `${text.slice(0, maxLength - 1)}…` : text
        },
        ruleHasPhone(row) {
            return !! row?.phone_key
        },
        ruleIsLaunched(row) {
            return !! row?.started_at && ! row?.archived_at
        },
        ruleIsLaunchable(row) {
            return this.ruleHasPhone(row) && ! this.ruleIsLaunched(row)
        },
        rowActionMode(row) {
            if (this.ruleIsLaunched(row)) {
                return 'stop'
            }

            if (this.ruleIsLaunchable(row)) {
                return 'launch'
            }

            return ''
        },
        branchRows(...groups) {
            return groups.flat().filter((row) => !! row)
        },
        mediumNodeRows(mediumNode) {
            return this.branchRows(mediumNode?.mediumRow, ...(mediumNode?.campaigns || []))
        },
        sourceNodeRuleRow(sourceNode) {
            return sourceNode?.sourceRule ?? this.sourceFallbackRow(sourceNode?.source)
        },
        sourceNodeRows(sourceNode) {
            return this.branchRows(
                this.sourceNodeRuleRow(sourceNode),
                ...(sourceNode?.mediums || []).flatMap((mediumNode) => [
                    mediumNode?.mediumRow,
                    ...(mediumNode?.campaigns || []),
                ]),
            )
        },
        branchActionMode(rows) {
            if (rows.some((row) => this.ruleIsLaunched(row))) {
                return 'stop'
            }

            if (rows.some((row) => this.ruleIsLaunchable(row))) {
                return 'launch'
            }

            return ''
        },
        branchActionKeys(rows) {
            const mode = this.branchActionMode(rows)

            if (mode === 'stop') {
                return rows.filter((row) => this.ruleIsLaunched(row) && ! String(row.key || '').startsWith('source-preview-')).map((row) => row.key)
            }

            if (mode === 'launch') {
                return rows.filter((row) => this.ruleIsLaunchable(row) && ! String(row.key || '').startsWith('source-preview-')).map((row) => row.key)
            }

            return []
        },
        sourceNodeActionMode(sourceNode) {
            return this.branchActionMode(this.sourceNodeRows(sourceNode))
        },
        mediumNodeActionMode(mediumNode) {
            return this.branchActionMode(this.mediumNodeRows(mediumNode))
        },
        sourceNodeActionKeys(sourceNode) {
            return this.branchActionKeys(this.sourceNodeRows(sourceNode))
        },
        mediumNodeActionKeys(mediumNode) {
            return this.branchActionKeys(this.mediumNodeRows(mediumNode))
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
                is_active: ! [false, 0, '0'].includes(row?.is_active),
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
                    cabinet: this.normalizeCabinet(row?.cabinet),
                    vk_app_enabled: !! row?.vk_app_enabled,
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
            return this.sourceRow(row?.source_key)?.source || ''
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
        setCampaignPhone(row, phoneKey) {
            row.phone_key = String(phoneKey || '')

            if (! row.phone_key) {
                row.started_at = ''
            }

            this.syncState()
        },
        setCampaignWidget(row, value) {
            row.open_booking_widget = !! value

            if (row.open_booking_widget) {
                row.vk_app_enabled = false
            }

            this.syncState()
        },
        setCampaignVkApp(row, value) {
            row.vk_app_enabled = !! value

            if (row.vk_app_enabled) {
                row.open_booking_widget = false
            }

            this.syncState()
        },
        buildUrl(sourceValue = '', mediumValue = '', campaignValue = '', shouldOpenWidget = false, useVkApp = false) {
            const source = String(sourceValue || '').trim()
            const medium = String(mediumValue || '').trim()
            const campaign = String(campaignValue || '').trim()

            if (! source) {
                return this.trackingBaseUrl
            }

            if (useVkApp && this.vkMiniAppId && this.trackingCitySlug) {
                const params = new URLSearchParams()
                params.set('city', this.trackingCitySlug)
                params.set('utm_source', source)

                if (medium) {
                    params.set('utm_medium', medium)
                }

                if (campaign) {
                    params.set('utm_campaign', campaign)
                }

                return `https://vk.com/app${this.vkMiniAppId}#${params.toString()}`
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
            if (! campaignRow) {
                return ''
            }

            return this.buildUrl(
                this.campaignSourceValue(campaignRow),
                ['medium', 'campaign'].includes(campaignRow.type) ? campaignRow.medium : '',
                campaignRow.type === 'campaign' ? campaignRow.campaign : '',
                campaignRow.open_booking_widget,
                campaignRow.vk_app_enabled,
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
            if (! campaignRow) {
                return
            }

            const link = this.trackingLinkValue(campaignRow)
            const copied = await this.copyText(link)

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

            this.showToast('Ссылка скопирована', this.shortToastText(link))
        },
        isTrackingLinkCopied(campaignRow) {
            return !! campaignRow && this.copiedTrackingKey === campaignRow.key
        },
        async copySelectedPhone(phoneKey, contextKey) {
            const phone = this.phoneLabel(phoneKey)
            const copied = await this.copyText(phone)

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

            this.showToast('Телефон скопирован', phone)
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
        sourceMatchesTrackingFilters(sourceRow) {
            const filters = this.trackingFilters
            const search = String(this.trackingSearch || '').trim().toLowerCase()
            const phoneKey = sourceRow?.default_phone_key || ''

            if (filters.source_key && sourceRow?.key !== filters.source_key) {
                return false
            }

            if (filters.phone_key && phoneKey !== filters.phone_key) {
                return false
            }

            if (filters.status || filters.started_date) {
                return false
            }

            if (! search) {
                return true
            }

            return [
                sourceRow?.source || '',
                sourceRow?.name || '',
                this.phoneLabel(phoneKey),
                this.trackingLinkValue(this.sourceFallbackRow(sourceRow)),
            ].join(' ').toLowerCase().includes(search)
        },
        sourceHasArchivedBranchRules(sourceKey) {
            return this.state.archived_campaigns.some((row) => row.source_key === sourceKey)
        },
        sourceHasTrackingRules(sourceKey) {
            return this.state.campaigns.some((row) => row.source_key === sourceKey)
        },
        shouldSeedTrackingSource(sourceRow) {
            if (! sourceRow) {
                return false
            }

            if (this.sourceHasTrackingRules(sourceRow.key)) {
                return true
            }

            return ! this.sourceHasArchivedBranchRules(sourceRow.key)
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

            if (mode === 'tracking') {
                this.state.sources
                    .filter((sourceRow) => this.sourceMatchesTrackingFilters(sourceRow))
                    .filter((sourceRow) => this.shouldSeedTrackingSource(sourceRow))
                    .forEach((sourceRow) => {
                        sourceMap.set(sourceRow.key, {
                            key: sourceRow.key,
                            source: sourceRow,
                            sourceRule: null,
                            mediums: new Map(),
                            looseCampaigns: [],
                        })
                    })
            }

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

            const sourceNodes = Array.from(sourceMap.values())
                .map((sourceNode) => {
                    if (mode === 'tracking' && sourceNode.sourceRule) {
                        sourceNode.source.phone_key = sourceNode.source.default_phone_key || ''
                        sourceNode.source.open_booking_widget = !! sourceNode.sourceRule.open_booking_widget
                    } else {
                        sourceNode.source.phone_key = sourceNode.source.default_phone_key || ''
                    }

                    return {
                        ...sourceNode,
                        mediums: (mode === 'tracking'
                            ? Array.from(sourceNode.mediums.values())
                            : Array.from(sourceNode.mediums.values()).sort((left, right) => String(left.medium).localeCompare(String(right.medium), undefined, { numeric: true, sensitivity: 'base' }))
                        )
                        .map((mediumNode) => ({
                            ...mediumNode,
                            key: mediumNode.mediumRow?.key || mediumNode.campaigns?.[0]?.key || mediumNode.key,
                            campaigns: this.sortCampaignRows(mediumNode.campaigns, mode),
                        })),
                    }
                })

            if (mode === 'tracking') {
                return sourceNodes
            }

            return sourceNodes.sort((left, right) => this.sourceLabel(left.key).localeCompare(this.sourceLabel(right.key), undefined, { numeric: true, sensitivity: 'base' }))
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
        mediumNodeName(mediumNode) {
            return mediumNode?.mediumRow?.medium_name || mediumNode?.campaigns?.[0]?.medium_name || ''
        },
        setMediumNodeName(mediumNode, value) {
            const nextValue = String(value ?? '')

            if (mediumNode?.mediumRow) {
                mediumNode.mediumRow.medium_name = nextValue
            }

            ;(mediumNode?.campaigns || []).forEach((row) => {
                row.medium_name = nextValue
            })

            this.syncState()
        },
        setMediumNodeValue(sourceKey, mediumNode, value) {
            const previousMedium = String(mediumNode?.medium || '')
            const nextMedium = String(value ?? '')

            if (mediumNode?.mediumRow) {
                mediumNode.mediumRow.medium = nextMedium
            }

            ;(mediumNode?.campaigns || []).forEach((row) => {
                row.medium = nextMedium
            })

            if (mediumNode) {
                mediumNode.medium = nextMedium
            }

            const previousKey = this.mediumTreeKey(sourceKey, previousMedium)
            const nextKey = this.mediumTreeKey(sourceKey, nextMedium)

            this.expandedMediums.tracking = this.expandedMediums.tracking.map((key) => key === previousKey ? nextKey : key)
            this.expandedMediums.archive = this.expandedMediums.archive.map((key) => key === previousKey ? nextKey : key)

            this.syncState()
        },
        mediumNodePreviewRow(sourceKey, mediumNode) {
            return mediumNode?.mediumRow ?? {
                key: `medium-preview-${sourceKey}-${mediumNode?.medium || '__empty_medium__'}`,
                id: null,
                type: 'medium',
                source_key: sourceKey,
                medium: mediumNode?.medium || '',
                medium_name: this.mediumNodeName(mediumNode),
                campaign: '',
                campaign_name: '',
                phone_key: '',
                open_booking_widget: false,
                cabinet: '',
                vk_app_enabled: false,
                is_organic: false,
                is_organic_overridden: false,
                effective_is_organic: this.campaignSourceIsOrganic({ source_key: sourceKey }),
                created_at: '',
                started_at: '',
                stopped_at: '',
                archived_at: '',
                restarted_from_id: null,
            }
        },
        ensureMediumRow(sourceKey, mediumNode) {
            if (! sourceKey || ! mediumNode) {
                return null
            }

            if (mediumNode.mediumRow) {
                return mediumNode.mediumRow
            }

            const now = this.currentDateTimeValue()
            const row = {
                key: this.makeKey('campaign'),
                id: null,
                type: 'medium',
                source_key: sourceKey,
                medium: mediumNode.medium || '',
                medium_name: this.mediumNodeName(mediumNode),
                campaign: '',
                campaign_name: '',
                phone_key: '',
                open_booking_widget: false,
                cabinet: '',
                vk_app_enabled: false,
                is_organic: false,
                is_organic_overridden: false,
                effective_is_organic: this.campaignSourceIsOrganic({ source_key: sourceKey }),
                created_at: now,
                started_at: '',
                stopped_at: '',
                archived_at: '',
                restarted_from_id: null,
            }

            mediumNode.mediumRow = row
            this.state.campaigns.unshift(row)

            return row
        },
        setMediumNodePhone(sourceKey, mediumNode, phoneKey) {
            const row = this.ensureMediumRow(sourceKey, mediumNode)

            row.phone_key = String(phoneKey || '')

            if (! row.phone_key) {
                row.started_at = ''
            }

            this.syncState()
        },
        setMediumNodeWidget(sourceKey, mediumNode, value) {
            const row = this.ensureMediumRow(sourceKey, mediumNode)

            row.open_booking_widget = !! value

            if (row.open_booking_widget) {
                row.vk_app_enabled = false
            }

            this.syncState()
        },
        setMediumNodeCabinet(sourceKey, mediumNode, value) {
            const row = this.ensureMediumRow(sourceKey, mediumNode)

            row.cabinet = this.normalizeCabinet(value)
            this.syncState()
        },
        setMediumNodeVkApp(sourceKey, mediumNode, value) {
            const row = this.ensureMediumRow(sourceKey, mediumNode)

            row.vk_app_enabled = !! value

            if (row.vk_app_enabled) {
                row.open_booking_widget = false
            }

            this.syncState()
        },
        setMediumNodeOrganic(sourceKey, mediumNode, value) {
            const row = this.ensureMediumRow(sourceKey, mediumNode)

            if (! row) {
                return
            }

            this.setCampaignOrganic(row, value)
        },
        mediumNodeEffectiveIsOrganic(sourceKey, mediumNode) {
            return mediumNode?.mediumRow
                ? this.campaignEffectiveIsOrganic(mediumNode.mediumRow)
                : this.campaignSourceIsOrganic({ source_key: sourceKey })
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
                cabinet: '',
                vk_app_enabled: false,
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
        ensureSourceRule(sourceNode, force = false) {
            if (! sourceNode?.source) {
                return null
            }

            if (sourceNode.sourceRule) {
                return sourceNode.sourceRule
            }

            if (! force && ! sourceNode.source.default_phone_key && ! sourceNode.source.open_booking_widget) {
                return null
            }

            const now = this.currentDateTimeValue()
            const row = {
                key: this.makeKey('campaign'),
                id: null,
                type: 'source',
                source_key: sourceNode.key,
                medium: '',
                medium_name: '',
                campaign: '',
                campaign_name: '',
                phone_key: sourceNode.source.default_phone_key || '',
                open_booking_widget: !! sourceNode.source.open_booking_widget,
                cabinet: '',
                vk_app_enabled: false,
                is_organic: false,
                is_organic_overridden: false,
                effective_is_organic: !! sourceNode.source.is_organic,
                created_at: now,
                started_at: '',
                stopped_at: '',
                archived_at: '',
                restarted_from_id: null,
            }

            sourceNode.sourceRule = row
            this.state.campaigns.unshift(row)

            return row
        },
        setSourceRuleCabinet(sourceNode, value) {
            const row = this.ensureSourceRule(sourceNode, true)

            if (! row) {
                return
            }

            row.cabinet = this.normalizeCabinet(value)
            this.syncState()
        },
        setSourceRuleVkApp(sourceNode, value) {
            const row = this.ensureSourceRule(sourceNode, true)

            if (! row) {
                return
            }

            row.vk_app_enabled = !! value

            if (row.vk_app_enabled) {
                row.open_booking_widget = false
                sourceNode.source.open_booking_widget = false
            }

            this.syncState()
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
                this.state.campaigns = this.state.campaigns.map((row) => {
                    if (row.type !== 'source' || row.source_key !== sourceKey) {
                        return row
                    }

                    return {
                        ...row,
                        phone_key: '',
                        started_at: '',
                    }
                })

                if (sourceNode.sourceRule) {
                    sourceNode.sourceRule.phone_key = ''
                    sourceNode.sourceRule.started_at = ''
                }

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

            if (! this.state.campaigns.some((row) => row.type === 'source' && row.source_key === sourceKey)) {
                this.state.campaigns.unshift({
                    key: this.makeKey('campaign'),
                    id: null,
                    type: 'source',
                    source_key: sourceKey,
                    medium: '',
                    medium_name: '',
                    campaign: '',
                    campaign_name: '',
                    phone_key: value,
                    open_booking_widget: !! sourceNode.source.open_booking_widget,
                    cabinet: '',
                    vk_app_enabled: false,
                    is_organic: false,
                    is_organic_overridden: false,
                    effective_is_organic: !! sourceNode.source.is_organic,
                    created_at: this.currentDateTimeValue(),
                    started_at: '',
                    stopped_at: '',
                    archived_at: '',
                    restarted_from_id: null,
                })
            }

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
                is_active: true,
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
            const key = this.makeKey('source')

            this.state.sources.unshift({
                key,
                id: null,
                source: '',
                name: '',
                default_phone_key: '',
                open_booking_widget: false,
                is_organic: false,
            })

            this.activeTab = 'tracking'
            this.trackingSearch = ''
            this.trackingFilters = {
                source_key: '',
                phone_key: '',
                started_date: '',
            }
            this.expandedSources.tracking = Array.from(new Set([key, ...this.expandedSources.tracking]))
            this.syncState()
        },
        deleteSource(sourceKey) {
            this.state.sources = this.state.sources.filter((row) => row.key !== sourceKey)
            this.state.campaigns = this.state.campaigns.filter((row) => row.source_key !== sourceKey)
            this.state.archived_campaigns = this.state.archived_campaigns.filter((row) => row.source_key !== sourceKey)
            this.syncState()
        },
        deleteMediumTree(sourceKey, medium) {
            const mediumValue = String(medium || '')
            const removedKeys = this.state.campaigns
                .filter((row) => row.source_key === sourceKey && ['medium', 'campaign'].includes(row.type) && String(row.medium || '') === mediumValue)
                .map((row) => row.key)

            this.state.campaigns = this.state.campaigns.filter((row) => {
                return ! (row.source_key === sourceKey && ['medium', 'campaign'].includes(row.type) && String(row.medium || '') === mediumValue)
            })
            this.selectedTrackingKeys = this.selectedTrackingKeys.filter((key) => ! removedKeys.includes(key))
            this.expandedMediums.tracking = this.expandedMediums.tracking.filter((key) => key !== this.mediumTreeKey(sourceKey, mediumValue))
            this.syncState()
        },
        deleteCampaignRow(campaignKey) {
            this.state.campaigns = this.state.campaigns.filter((row) => row.key !== campaignKey)
            this.selectedTrackingKeys = this.selectedTrackingKeys.filter((key) => key !== campaignKey)
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
                const sourceDefaultNeedsRule = !! defaultPhoneKey || !! sourceRow.open_booking_widget
                const activeSourceHasPayload = activeSourceRows.some((row) => row.phone_key || row.open_booking_widget || row.cabinet || row.vk_app_enabled || row.is_organic_overridden)
                const shouldHaveRow = (sourceDefaultNeedsRule || activeSourceHasPayload) && (activeSourceRows.length > 0 || ! archivedSourceExists)

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
                        cabinet: '',
                        vk_app_enabled: false,
                        is_organic: false,
                        is_organic_overridden: false,
                        effective_is_organic: !! sourceRow.is_organic,
                        created_at: this.currentDateTimeValue(),
                        started_at: '',
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
                cabinet: '',
                vk_app_enabled: false,
                is_organic: false,
                is_organic_overridden: false,
                effective_is_organic: false,
                created_at: this.currentDateTimeValue(),
                started_at: '',
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
                cabinet: '',
                vk_app_enabled: false,
                is_organic: false,
                is_organic_overridden: false,
                effective_is_organic: false,
                created_at: this.currentDateTimeValue(),
                started_at: '',
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
        async requestLaunchCampaign(campaignKey) {
            this.syncState()
            await this.runAction(() => this.$wire.launchCampaign(campaignKey, this.state), 'tracking')
        },
        async requestToggleCampaign(row) {
            const mode = this.rowActionMode(row)

            if (mode === 'stop') {
                await this.requestStopCampaign(row.key)

                return
            }

            if (mode === 'launch') {
                await this.requestLaunchCampaign(row.key)
            }
        },
        async requestToggleBranch(keys, mode) {
            if (keys.length === 0 || ! mode) {
                return
            }

            this.syncState()
            await this.runAction(() => mode === 'stop'
                ? this.$wire.stopCampaigns(keys, this.state)
                : this.$wire.launchCampaigns(keys, this.state), 'tracking')
            this.showToast(mode === 'stop' ? 'Кампания остановлена' : 'Кампания запущена')
        },
        async requestToggleSource(sourceNode) {
            const mode = this.sourceNodeActionMode(sourceNode)
            if (mode === 'launch' && ! sourceNode?.sourceRule && sourceNode?.source?.default_phone_key) {
                this.ensureSourceRule(sourceNode, true)
            }

            const keys = this.sourceNodeActionKeys(sourceNode)

            if (keys.length === 0) {
                return
            }

            await this.requestToggleBranch(keys, mode)
        },
        async requestToggleMediumTree(mediumNode) {
            const mode = this.mediumNodeActionMode(mediumNode)
            const keys = this.mediumNodeActionKeys(mediumNode)

            if (keys.length === 0) {
                return
            }

            await this.requestToggleBranch(keys, mode)
        },
        async requestDeleteSourceTree(sourceKey) {
            this.deleteSource(sourceKey)
            await this.runAction(() => this.$wire.saveState(this.state), 'tracking')
            this.showToast('Source удалён')
        },
        async requestDeleteMediumTree(sourceKey, medium) {
            this.deleteMediumTree(sourceKey, medium)
            await this.runAction(() => this.$wire.saveState(this.state), 'tracking')
            this.showToast('Medium удалён')
        },
        async requestDeleteCampaignRow(campaignKey) {
            this.deleteCampaignRow(campaignKey)
            await this.runAction(() => this.$wire.saveState(this.state), 'tracking')
            this.showToast('Campaign удалён')
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
            this.showToast('Кампании остановлены')
        },
        async requestResumeCampaign(campaignKey) {
            this.syncState()
            await this.runAction(() => this.$wire.resumeCampaign(campaignKey, this.state), 'archive')
            this.showToast('Кампания возобновлена')
        },
        async requestDeleteCampaign(campaignKey) {
            this.syncState()
            await this.runAction(() => this.$wire.deleteCampaign(campaignKey, this.state), 'tracking')
            this.showToast('Кампания остановлена')
        },
        async requestDeleteArchivedCampaign(campaignKey) {
            this.syncState()
            await this.runAction(() => this.$wire.deleteArchivedCampaign(campaignKey, this.state), 'archive')
            this.showToast('Кампания удалена из архива')
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

            return this.isPhoneInactive(phoneKey)
                || this.isPhoneBusyInActiveCampaigns(phoneKey, { type: 'campaign', key: campaignRow?.key })
                || this.isPhoneUsedBySourceTemplateForCampaign(phoneKey, campaignRow)
        },
        phoneOptionLabel(phoneKey, campaignRow) {
            const phone = this.phoneLabel(phoneKey)

            if (campaignRow?.phone_key !== phoneKey && this.isPhoneInactive(phoneKey)) {
                return `${phone} (неактивен)`
            }

            return this.isPhoneOptionDisabled(phoneKey, campaignRow) ? `${phone} (занят)` : phone
        },
        isSourcePhoneOptionDisabled(phoneKey, sourceRow) {
            if (sourceRow?.default_phone_key === phoneKey) {
                return false
            }

            return this.isPhoneInactive(phoneKey)
                || this.isPhoneBusyInActiveCampaigns(phoneKey)
                || this.isPhoneUsedByOtherSourceTemplate(phoneKey, sourceRow)
        },
        sourcePhoneOptionLabel(phoneKey, sourceRow) {
            const phone = this.phoneLabel(phoneKey)

            if (sourceRow?.default_phone_key !== phoneKey && this.isPhoneInactive(phoneKey)) {
                return `${phone} (неактивен)`
            }

            return this.isSourcePhoneOptionDisabled(phoneKey, sourceRow) ? `${phone} (занят)` : phone
        },
        isPhoneInactive(phoneKey) {
            return ! this.isPhoneActive(phoneKey)
        },
        isPhoneActive(phoneKey) {
            const phone = this.state.phones.find((row) => row.key === phoneKey)

            return phone ? ! [false, 0, '0'].includes(phone.is_active) : true
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
    }
