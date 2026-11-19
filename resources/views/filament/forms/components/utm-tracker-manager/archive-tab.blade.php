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
                    <div class="utm-tracker-table">
                        <div class="utm-tracker-grid utm-tracker-grid--archive-tree border-b border-gray-200 bg-gray-50 px-3 py-2 text-[11px] font-medium uppercase text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                            <span>Источник / кампания</span>
                            <span>source</span>
                            <span>medium</span>
                            <span>campaign</span>
                            <span>Телефон</span>
                            <span>Кабинет</span>
                            <span>Виджет</span>
                            <span>VK app</span>
                            <span>Запуск</span>
                            <span>Остановка</span>
                            <span>Действие</span>
                        </div>

                        <template x-for="sourceNode in treeForView('archive')" :key="`archive-source-${sourceNode.key}`">
                            <div>
                                <div class="utm-tracker-grid utm-tracker-grid--archive-tree utm-tracker-row border-b border-gray-100 px-3 py-2 dark:border-white/10">
                                    <div class="utm-tracker-level utm-tracker-level--source">
                                        <button x-show="sourceNode.mediums.length > 0" type="button" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5" x-on:click="toggleSource('archive', sourceNode.key)" x-bind:title="isSourceExpanded('archive', sourceNode.key) ? 'Свернуть source' : 'Раскрыть source'"><svg class="h-4 w-4 transition" x-bind:class="isSourceExpanded('archive', sourceNode.key) ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg></button>
                                        <span x-show="sourceNode.mediums.length === 0" class="h-7 w-7 shrink-0"></span>
                                        <span class="utm-tracker-badge">SOURCE</span>
                                        <span class="truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="sourceNode.source?.name || sourceNode.source?.source || 'Без source'"></span>
                                    </div>
                                    <span class="utm-tracker-cell truncate font-mono text-xs text-gray-800 dark:text-gray-100" x-text="sourceNode.source?.source || '—'"></span>
                                    <span class="utm-tracker-cell text-xs text-gray-400">—</span>
                                    <span class="utm-tracker-cell text-xs text-gray-400">—</span>
                                    <span class="utm-tracker-cell truncate text-xs text-gray-800 dark:text-gray-100" x-text="sourceNode.sourceRule ? phoneLabel(sourceNode.sourceRule.phone_key) : '—'"></span>
                                    <span class="utm-tracker-cell truncate text-xs text-gray-800 dark:text-gray-100" x-text="sourceNode.sourceRule ? cabinetLabel(sourceNode.sourceRule.cabinet) : '—'"></span>
                                    <span class="text-xs text-gray-800 dark:text-gray-100" x-text="sourceNode.sourceRule ? (sourceNode.sourceRule.open_booking_widget ? 'Да' : 'Нет') : '—'"></span>
                                    <span class="text-xs text-gray-800 dark:text-gray-100" x-text="sourceNode.sourceRule ? (sourceNode.sourceRule.vk_app_enabled ? 'Да' : 'Нет') : '—'"></span>
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
                                            <div class="utm-tracker-grid utm-tracker-grid--archive-tree utm-tracker-row border-b border-gray-100 bg-gray-50/70 px-3 py-2 dark:border-white/10 dark:bg-white/[0.03]">
                                                <div class="utm-tracker-level utm-tracker-level--medium">
                                                    <button x-show="mediumNode.campaigns.length > 0" type="button" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-white dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5" x-on:click="toggleMedium('archive', sourceNode.key, mediumNode.medium)" x-bind:title="isMediumExpanded('archive', sourceNode.key, mediumNode.medium) ? 'Свернуть medium' : 'Раскрыть medium'"><svg class="h-4 w-4 transition" x-bind:class="isMediumExpanded('archive', sourceNode.key, mediumNode.medium) ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg></button>
                                                    <span x-show="mediumNode.campaigns.length === 0" class="h-7 w-7 shrink-0"></span>
                                                    <span class="utm-tracker-badge">MEDIUM</span>
                                                    <span class="truncate text-xs font-medium text-gray-900 dark:text-white" x-text="mediumNode.mediumRow ? campaignName(mediumNode.mediumRow) : (mediumNode.medium || '—')"></span>
                                                </div>
                                                <span class="utm-tracker-cell truncate font-mono text-xs text-gray-700 dark:text-gray-200" x-text="campaignSourceValue(mediumNode.mediumRow) || sourceNode.source?.source || '—'"></span>
                                                <span class="utm-tracker-cell truncate font-mono text-xs text-gray-800 dark:text-gray-100" x-text="mediumNode.medium || '—'"></span>
                                                <span class="utm-tracker-cell text-xs text-gray-400">—</span>
                                                <span class="utm-tracker-cell truncate text-xs text-gray-800 dark:text-gray-100" x-text="mediumNode.mediumRow ? phoneLabel(mediumNode.mediumRow.phone_key) : '—'"></span>
                                                <span class="utm-tracker-cell truncate text-xs text-gray-800 dark:text-gray-100" x-text="mediumNode.mediumRow ? cabinetLabel(mediumNode.mediumRow.cabinet) : '—'"></span>
                                                <span class="text-xs text-gray-800 dark:text-gray-100" x-text="mediumNode.mediumRow ? (mediumNode.mediumRow.open_booking_widget ? 'Да' : 'Нет') : '—'"></span>
                                                <span class="text-xs text-gray-800 dark:text-gray-100" x-text="mediumNode.mediumRow ? (mediumNode.mediumRow.vk_app_enabled ? 'Да' : 'Нет') : '—'"></span>
                                                <span class="text-xs text-gray-700 dark:text-gray-100" x-text="formatDateTime(mediumNode.mediumRow?.started_at)"></span>
                                                <span class="text-xs text-gray-700 dark:text-gray-100" x-text="formatDateTime(mediumNode.mediumRow?.stopped_at || mediumNode.mediumRow?.archived_at)"></span>
                                                <div class="utm-tracker-actions">
                                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-emerald-200 text-emerald-600 transition hover:bg-emerald-50 dark:border-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="mediumNode.mediumRow ? '' : 'invisible'" x-bind:disabled="isPersisting || ! mediumNode.mediumRow" x-on:click="mediumNode.mediumRow && requestResumeCampaign(mediumNode.mediumRow.key)" title="Возобновить" aria-label="Возобновить"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M6.22 5.22a.75.75 0 0 1 1.06 0l6 4.25a.75.75 0 0 1 0 1.22l-6 4.25A.75.75 0 0 1 6 14.31V5.75a.75.75 0 0 1 .22-.53Z" clip-rule="evenodd" /></svg></button>
                                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="mediumNode.mediumRow ? '' : 'invisible'" x-bind:disabled="isPersisting || ! mediumNode.mediumRow" x-on:click="mediumNode.mediumRow && requestDeleteArchivedCampaign(mediumNode.mediumRow.key)" title="Удалить" aria-label="Удалить"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" /></svg></button>
                                                </div>
                                            </div>

                                            <div x-show="isMediumExpanded('archive', sourceNode.key, mediumNode.medium)">
                                                <template x-for="campaignRow in mediumNode.campaigns" :key="campaignRow.key">
                                                    <div class="utm-tracker-grid utm-tracker-grid--archive-tree utm-tracker-row border-b border-gray-100 px-3 py-2 dark:border-white/10">
                                                        <div class="utm-tracker-level utm-tracker-level--campaign">
                                                            <span class="h-7 w-7 shrink-0"></span>
                                                            <span class="utm-tracker-badge">CAMPAIGN</span>
                                                            <span class="truncate text-xs font-medium text-gray-900 dark:text-white" x-text="campaignName(campaignRow)"></span>
                                                        </div>
                                                        <span class="utm-tracker-cell truncate font-mono text-xs text-gray-700 dark:text-gray-200" x-text="campaignSourceValue(campaignRow) || '—'"></span>
                                                        <span class="utm-tracker-cell truncate font-mono text-xs text-gray-700 dark:text-gray-200" x-text="campaignRow.medium || '—'"></span>
                                                        <span class="utm-tracker-cell truncate font-mono text-xs text-gray-900 dark:text-white" x-text="campaignRow.campaign || '—'"></span>
                                                        <span class="utm-tracker-cell truncate text-xs text-gray-800 dark:text-gray-100" x-text="phoneLabel(campaignRow.phone_key)"></span>
                                                        <span class="utm-tracker-cell truncate text-xs text-gray-800 dark:text-gray-100" x-text="cabinetLabel(campaignRow.cabinet)"></span>
                                                        <span class="text-xs text-gray-800 dark:text-gray-100" x-text="campaignRow.open_booking_widget ? 'Да' : 'Нет'"></span>
                                                        <span class="text-xs text-gray-800 dark:text-gray-100" x-text="campaignRow.vk_app_enabled ? 'Да' : 'Нет'"></span>
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
