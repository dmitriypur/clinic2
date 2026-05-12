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
                        class="inline-flex h-8 shrink-0 items-center justify-center rounded-md border border-primary-200 px-3 text-xs font-medium text-primary-600 transition hover:bg-primary-50 dark:border-primary-500/30 dark:text-primary-300 dark:hover:bg-primary-500/10 disabled:cursor-not-allowed disabled:opacity-60"
                        x-bind:disabled="isPersisting"
                        x-on:click="addSource()"
                    >
                        + source
                    </button>

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
                    <div class="utm-tracker-table">
                        <div class="utm-tracker-grid utm-tracker-grid--tree border-b border-gray-200 bg-gray-50 px-3 py-2 text-[11px] font-medium uppercase text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                            <span>Источник / кампания</span>
                            <span>source</span>
                            <span>medium</span>
                            <span>campaign</span>
                            <span>Телефон</span>
                            <span>Кабинет</span>
                            <span>Виджет</span>
                            <span>Орг.</span>
                            <span>VK app</span>
                            <span>Ссылка</span>
                            <span>Запуск</span>
                            <span>Действие</span>
                        </div>

                        <template x-for="sourceNode in treeForView('tracking')" :key="`tracking-source-${sourceNode.key}`">
                            <div>
                                <div class="utm-tracker-grid utm-tracker-grid--tree utm-tracker-row border-b border-gray-100 px-3 py-2 dark:border-white/10">
                                    <div class="utm-tracker-level utm-tracker-level--source">
                                        <button x-show="sourceNode.mediums.length > 0" type="button" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5" x-on:click="toggleSource('tracking', sourceNode.key)" x-bind:title="isSourceExpanded('tracking', sourceNode.key) ? 'Свернуть source' : 'Раскрыть source'">
                                            <svg class="h-4 w-4 transition" x-bind:class="isSourceExpanded('tracking', sourceNode.key) ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                                        </button>
                                        <span x-show="sourceNode.mediums.length === 0" class="h-7 w-7 shrink-0"></span>
                                        <span class="utm-tracker-badge">SOURCE</span>
                                        <input
                                            type="text"
                                            class="block min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                            x-model="sourceNode.source.name"
                                            x-bind:disabled="isPersisting || ! sourceNode.source"
                                            x-on:input.debounce.300ms="syncState()"
                                            placeholder="Название"
                                        />
                                    </div>
                                    <input
                                        type="text"
                                        class="block w-full rounded-md border-gray-300 px-2 py-1.5 font-mono text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                        x-model="sourceNode.source.source"
                                        x-bind:disabled="isPersisting || ! sourceNode.source"
                                        x-on:input.debounce.300ms="syncState()"
                                        placeholder="utm_source"
                                    />
                                    <span class="utm-tracker-cell text-xs text-gray-400">—</span>
                                    <span class="utm-tracker-cell text-xs text-gray-400">—</span>
                                    <div class="utm-tracker-phone-cell">
                                        <select
                                            class="block min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white"
                                            x-model="sourceNode.source.default_phone_key"
                                            x-bind:disabled="isPersisting || ! sourceNode.source"
                                            x-on:change="setSourcePhone(sourceNode, $event.target.value)"
                                        >
                                            <option value="">Без телефона</option>
                                            <template x-for="phoneRow in state.phones" :key="`tree-source-phone-${sourceNode.key}-${phoneRow.key}`">
                                                <option
                                                    x-bind:disabled="isSourcePhoneOptionDisabled(phoneRow.key, sourceNode.source)"
                                                    x-bind:selected="sourceNode.source.default_phone_key === phoneRow.key"
                                                    x-bind:value="phoneRow.key"
                                                    x-text="sourcePhoneOptionLabel(phoneRow.key, sourceNode.source)"
                                                ></option>
                                            </template>
                                        </select>
                                        <button type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-40" x-bind:class="isPhoneCopied(`tree-source-${sourceNode.key}`) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200' : ''" x-bind:disabled="isPersisting || ! sourceNode.source?.default_phone_key" x-on:click="copySelectedPhone(sourceNode.source.default_phone_key, `tree-source-${sourceNode.key}`)" title="Скопировать телефон" aria-label="Скопировать телефон"><svg x-show="! isPhoneCopied(`tree-source-${sourceNode.key}`)" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 3.5A2.5 2.5 0 0 1 9.5 1h5A2.5 2.5 0 0 1 17 3.5v8A2.5 2.5 0 0 1 14.5 14h-5A2.5 2.5 0 0 1 7 11.5v-8Z" /><path d="M4.5 6A2.5 2.5 0 0 0 2 8.5v8A2.5 2.5 0 0 0 4.5 19h5a2.5 2.5 0 0 0 2.45-2H4.5a.5.5 0 0 1-.5-.5v-8a.5.5 0 0 1 .5-.5V6Z" /></svg><svg x-show="isPhoneCopied(`tree-source-${sourceNode.key}`)" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z" clip-rule="evenodd" /></svg></button>
                                    </div>
                                    <select class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-bind:value="sourceFallbackRow(sourceNode.source)?.cabinet || ''" x-bind:disabled="isPersisting || ! sourceNode.source" x-on:change="setSourceRuleCabinet(sourceNode, $event.target.value)">
                                        <option value="" x-bind:selected="! sourceFallbackRow(sourceNode.source)?.cabinet">—</option>
                                        <template x-for="(label, value) in cabinetOptions" :key="`source-cabinet-${sourceNode.key}-${value}`">
                                            <option x-bind:value="value" x-bind:selected="sourceFallbackRow(sourceNode.source)?.cabinet === value" x-text="label"></option>
                                        </template>
                                    </select>
                                    <input type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-400 text-primary-600 focus:ring-primary-500 dark:border-white/30 dark:bg-transparent" x-bind:checked="!! sourceNode.source.open_booking_widget" x-bind:disabled="isPersisting || ! sourceNode.source" x-on:change="sourceNode.source.open_booking_widget = $event.target.checked; if (sourceNode.sourceRule) { sourceNode.sourceRule.open_booking_widget = sourceNode.source.open_booking_widget; if (sourceNode.sourceRule.open_booking_widget) sourceNode.sourceRule.vk_app_enabled = false; } syncState()" />
                                    <input type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-400 text-primary-600 focus:ring-primary-500 dark:border-white/30 dark:bg-transparent" x-model="sourceNode.source.is_organic" x-bind:disabled="isPersisting || ! sourceNode.source" x-on:change="syncState()" />
                                    <input type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-400 text-primary-600 focus:ring-primary-500 dark:border-white/30 dark:bg-transparent" x-bind:checked="!! sourceFallbackRow(sourceNode.source)?.vk_app_enabled" x-bind:disabled="isPersisting || ! sourceNode.source" x-on:change="setSourceRuleVkApp(sourceNode, $event.target.checked)" />
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-40" x-bind:class="isTrackingLinkCopied(sourceFallbackRow(sourceNode.source)) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200' : ''" x-on:click="copyTrackingLink(sourceFallbackRow(sourceNode.source))" x-bind:title="isTrackingLinkCopied(sourceFallbackRow(sourceNode.source)) ? 'Скопировано' : 'Скопировать ссылку'" aria-label="Скопировать ссылку"><svg x-show="! isTrackingLinkCopied(sourceFallbackRow(sourceNode.source))" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.586 4.586a2 2 0 0 1 2.828 2.828l-3 3a2 2 0 0 1-2.828 0 .75.75 0 0 0-1.06 1.06 3.5 3.5 0 0 0 4.95 0l3-3a3.5 3.5 0 0 0-4.95-4.95l-1.5 1.5a.75.75 0 0 0 1.06 1.061l1.5-1.5Zm-5.172 10.828a2 2 0 0 1-2.828-2.828l3-3a2 2 0 0 1 2.828 0 .75.75 0 1 0 1.06-1.06 3.5 3.5 0 0 0-4.95 0l-3 3a3.5 3.5 0 0 0 4.95 4.95l1.5-1.5a.75.75 0 1 0-1.06-1.061l-1.5 1.5Z" clip-rule="evenodd" /></svg><svg x-show="isTrackingLinkCopied(sourceFallbackRow(sourceNode.source))" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z" clip-rule="evenodd" /></svg></button>
                                    <span class="text-xs text-gray-700 dark:text-gray-100" x-text="ruleIsLaunched(sourceNode.sourceRule) ? formatDateTime(sourceNode.sourceRule?.started_at) : '—'"></span>
                                    <div class="utm-tracker-actions">
                                        <button type="button" class="utm-tracker-action-link text-xs font-medium text-primary-600 hover:underline dark:text-primary-400 disabled:cursor-not-allowed disabled:opacity-60" x-bind:disabled="isPersisting || ! sourceNode.source" x-on:click="addMediumForSource(sourceNode.key)">+ medium</button>
                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-400 text-gray-600 transition hover:bg-gray-50 dark:border-white/30 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="sourceNodeActionMode(sourceNode) ? '' : 'invisible'" x-bind:disabled="isPersisting || ! sourceNodeActionMode(sourceNode)" x-on:click="requestToggleSource(sourceNode)" x-bind:title="sourceNodeActionMode(sourceNode) === 'stop' ? 'Остановить ветку source' : 'Запустить ветку source'" x-bind:aria-label="sourceNodeActionMode(sourceNode) === 'stop' ? 'Остановить ветку source' : 'Запустить ветку source'"><svg x-show="sourceNodeActionMode(sourceNode) === 'stop'" class="h-4 w-4" viewBox="0 0 20 20" fill="#5f6c82" aria-hidden="true"><path d="M6 6.75A.75.75 0 0 1 6.75 6h6.5a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-.75.75h-6.5A.75.75 0 0 1 6 13.25v-6.5Z" /></svg><svg x-show="sourceNodeActionMode(sourceNode) === 'launch'" class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path d="M6.25 4.7a.9.9 0 0 1 1.36-.77l7.25 4.55a.9.9 0 0 1 0 1.54l-7.25 4.55a.9.9 0 0 1-1.36-.77V4.7Z" /></svg></button>
                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:disabled="isPersisting || ! sourceNode.source" x-on:click="requestDeleteSourceTree(sourceNode.key)" title="Удалить source" aria-label="Удалить source"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" /></svg></button>
                                    </div>
                                </div>

                                <div x-show="isSourceExpanded('tracking', sourceNode.key)">
                                    <template x-for="mediumNode in sourceNode.mediums" :key="`tracking-medium-${sourceNode.key}-${mediumNode.key}`">
                                        <div>
                                            <div class="utm-tracker-grid utm-tracker-grid--tree utm-tracker-row border-b border-gray-100 bg-gray-50/70 px-3 py-2 dark:border-white/10 dark:bg-white/[0.03]">
                                                <div class="utm-tracker-level utm-tracker-level--medium">
                                                    <button x-show="mediumNode.campaigns.length > 0" type="button" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-white dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5" x-on:click="toggleMedium('tracking', sourceNode.key, mediumNode.medium)" x-bind:title="isMediumExpanded('tracking', sourceNode.key, mediumNode.medium) ? 'Свернуть medium' : 'Раскрыть medium'"><svg class="h-4 w-4 transition" x-bind:class="isMediumExpanded('tracking', sourceNode.key, mediumNode.medium) ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg></button>
                                                    <span x-show="mediumNode.campaigns.length === 0" class="h-7 w-7 shrink-0"></span>
                                                    <span class="utm-tracker-badge">MEDIUM</span>
                                                    <input type="text" class="block min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-bind:value="mediumNodeName(mediumNode)" x-bind:disabled="isPersisting" x-on:input.debounce.300ms="setMediumNodeName(mediumNode, $event.target.value)" placeholder="Название" />
                                                </div>
                                                <span class="utm-tracker-cell truncate font-mono text-xs text-gray-700 dark:text-gray-200" x-text="campaignSourceValue(mediumNode.mediumRow) || sourceNode.source?.source || '—'"></span>
                                                <input type="text" class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-bind:value="mediumNode.medium" x-bind:disabled="isPersisting" x-on:input.debounce.300ms="setMediumNodeValue(sourceNode.key, mediumNode, $event.target.value)" placeholder="utm_medium" />
                                                <span class="utm-tracker-cell text-xs text-gray-400">—</span>
                                                <div class="utm-tracker-phone-cell">
                                                    <select class="block min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-bind:class="isDuplicateCampaignPhone(mediumNode.mediumRow) ? 'border-rose-500 bg-rose-50 text-rose-700 ring-1 ring-rose-400 focus:border-rose-500 focus:ring-rose-500 dark:border-rose-500/70 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/40' : ''" x-bind:value="mediumNode.mediumRow?.phone_key || ''" x-bind:disabled="isPersisting" x-on:change="setMediumNodePhone(sourceNode.key, mediumNode, $event.target.value)"><option value="">Без телефона</option><template x-for="phoneRow in state.phones" :key="phoneRow.key"><option x-bind:disabled="isPhoneOptionDisabled(phoneRow.key, mediumNode.mediumRow || mediumNodePreviewRow(sourceNode.key, mediumNode))" x-bind:selected="(mediumNode.mediumRow?.phone_key || '') === phoneRow.key" x-bind:value="phoneRow.key" x-text="phoneOptionLabel(phoneRow.key, mediumNode.mediumRow || mediumNodePreviewRow(sourceNode.key, mediumNode))"></option></template></select>
                                                    <button type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-white dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40" x-bind:class="isPhoneCopied(`tree-${mediumNode.mediumRow?.key}`) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200' : ''" x-bind:disabled="isPersisting || ! mediumNode.mediumRow?.phone_key" x-on:click="mediumNode.mediumRow && copySelectedPhone(mediumNode.mediumRow.phone_key, `tree-${mediumNode.mediumRow.key}`)" title="Скопировать телефон" aria-label="Скопировать телефон"><svg x-show="! isPhoneCopied(`tree-${mediumNode.mediumRow?.key}`)" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 3.5A2.5 2.5 0 0 1 9.5 1h5A2.5 2.5 0 0 1 17 3.5v8A2.5 2.5 0 0 1 14.5 14h-5A2.5 2.5 0 0 1 7 11.5v-8Z" /><path d="M4.5 6A2.5 2.5 0 0 0 2 8.5v8A2.5 2.5 0 0 0 4.5 19h5a2.5 2.5 0 0 0 2.45-2H4.5a.5.5 0 0 1-.5-.5v-8a.5.5 0 0 1 .5-.5V6Z" /></svg><svg x-show="isPhoneCopied(`tree-${mediumNode.mediumRow?.key}`)" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z" clip-rule="evenodd" /></svg></button>
                                                </div>
                                                <select class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-bind:value="mediumNode.mediumRow?.cabinet || ''" x-bind:disabled="isPersisting" x-on:change="setMediumNodeCabinet(sourceNode.key, mediumNode, $event.target.value)">
                                                    <option value="" x-bind:selected="! mediumNode.mediumRow?.cabinet">—</option>
                                                    <template x-for="(label, value) in cabinetOptions" :key="`medium-cabinet-${sourceNode.key}-${mediumNode.key}-${value}`">
                                                        <option x-bind:value="value" x-bind:selected="mediumNode.mediumRow?.cabinet === value" x-text="label"></option>
                                                    </template>
                                                </select>
                                                <input type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent" x-bind:checked="!! mediumNode.mediumRow?.open_booking_widget" x-bind:disabled="isPersisting" x-on:change="setMediumNodeWidget(sourceNode.key, mediumNode, $event.target.checked)" />
                                                <input type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent" x-bind:checked="mediumNodeEffectiveIsOrganic(sourceNode.key, mediumNode)" x-bind:disabled="isPersisting" x-on:change="setMediumNodeOrganic(sourceNode.key, mediumNode, $event.target.checked)" />
                                                <input type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent" x-bind:checked="!! mediumNode.mediumRow?.vk_app_enabled" x-bind:disabled="isPersisting" x-on:change="setMediumNodeVkApp(sourceNode.key, mediumNode, $event.target.checked)" />
                                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-white dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40" x-bind:class="isTrackingLinkCopied(mediumNode.mediumRow || mediumNodePreviewRow(sourceNode.key, mediumNode)) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200' : ''" x-on:click="copyTrackingLink(mediumNode.mediumRow || mediumNodePreviewRow(sourceNode.key, mediumNode))" x-bind:title="isTrackingLinkCopied(mediumNode.mediumRow || mediumNodePreviewRow(sourceNode.key, mediumNode)) ? 'Скопировано' : 'Скопировать ссылку'" aria-label="Скопировать ссылку"><svg x-show="! isTrackingLinkCopied(mediumNode.mediumRow || mediumNodePreviewRow(sourceNode.key, mediumNode))" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.586 4.586a2 2 0 0 1 2.828 2.828l-3 3a2 2 0 0 1-2.828 0 .75.75 0 0 0-1.06 1.06 3.5 3.5 0 0 0 4.95 0l3-3a3.5 3.5 0 0 0-4.95-4.95l-1.5 1.5a.75.75 0 0 0 1.06 1.061l1.5-1.5Zm-5.172 10.828a2 2 0 0 1-2.828-2.828l3-3a2 2 0 0 1 2.828 0 .75.75 0 1 0 1.06-1.06 3.5 3.5 0 0 0-4.95 0l-3 3a3.5 3.5 0 0 0 4.95 4.95l1.5-1.5a.75.75 0 1 0-1.06-1.061l-1.5 1.5Z" clip-rule="evenodd" /></svg><svg x-show="isTrackingLinkCopied(mediumNode.mediumRow || mediumNodePreviewRow(sourceNode.key, mediumNode))" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z" clip-rule="evenodd" /></svg></button>
                                                <span class="text-xs text-gray-700 dark:text-gray-100" x-text="ruleIsLaunched(mediumNode.mediumRow) ? formatDateTime(mediumNode.mediumRow?.started_at) : '—'"></span>
                                                <div class="utm-tracker-actions">
                                                    <button type="button" class="utm-tracker-action-link text-xs font-medium text-primary-600 hover:underline dark:text-primary-400 disabled:cursor-not-allowed disabled:opacity-60" x-bind:disabled="isPersisting || ! mediumNode.medium" x-on:click="addCampaignForMedium(sourceNode.key, mediumNode.medium)">+ campaign</button>
                                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-white dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="mediumNodeActionMode(mediumNode) ? '' : 'invisible'" x-bind:disabled="isPersisting || ! mediumNodeActionMode(mediumNode)" x-on:click="requestToggleMediumTree(mediumNode)" x-bind:title="mediumNodeActionMode(mediumNode) === 'stop' ? 'Остановить ветку medium' : 'Запустить ветку medium'" x-bind:aria-label="mediumNodeActionMode(mediumNode) === 'stop' ? 'Остановить ветку medium' : 'Запустить ветку medium'"><svg x-show="mediumNodeActionMode(mediumNode) === 'stop'" class="h-4 w-4" viewBox="0 0 20 20" fill="#374151" aria-hidden="true"><path d="M6 6.75A.75.75 0 0 1 6.75 6h6.5a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-.75.75h-6.5A.75.75 0 0 1 6 13.25v-6.5Z" /></svg><svg x-show="mediumNodeActionMode(mediumNode) === 'launch'" class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path d="M6.25 4.7a.9.9 0 0 1 1.36-.77l7.25 4.55a.9.9 0 0 1 0 1.54l-7.25 4.55a.9.9 0 0 1-1.36-.77V4.7Z" /></svg></button>
                                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:disabled="isPersisting" x-on:click="requestDeleteMediumTree(sourceNode.key, mediumNode.medium)" title="Удалить medium" aria-label="Удалить medium"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" /></svg></button>
                                                </div>
                                            </div>

                                            <div x-show="isMediumExpanded('tracking', sourceNode.key, mediumNode.medium)">
                                                <template x-for="campaignRow in mediumNode.campaigns" :key="campaignRow.key">
                                                    <div class="utm-tracker-grid utm-tracker-grid--tree utm-tracker-row border-b border-gray-100 px-3 py-2 dark:border-white/10" x-bind:class="isDuplicateCampaignPhone(campaignRow) ? 'bg-rose-50 dark:bg-rose-500/10' : 'bg-white dark:bg-gray-900'">
                                                        <div class="utm-tracker-level utm-tracker-level--campaign">
                                                            <span class="h-7 w-7 shrink-0"></span>
                                                            <span class="utm-tracker-badge">CAMPAIGN</span>
                                                            <input type="text" class="block min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-model="campaignRow.campaign_name" x-bind:disabled="isPersisting" x-on:input.debounce.300ms="syncState()" placeholder="Название" />
                                                        </div>
                                                        <span class="utm-tracker-cell truncate font-mono text-xs text-gray-700 dark:text-gray-200" x-text="campaignSourceValue(campaignRow) || '—'"></span>
                                                        <span class="utm-tracker-cell truncate font-mono text-xs text-gray-700 dark:text-gray-200" x-text="campaignRow.medium || '—'"></span>
                                                        <input type="text" class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-model="campaignRow.campaign" x-bind:disabled="isPersisting" x-on:input.debounce.300ms="syncState()" placeholder="utm_campaign" />
                                                        <div class="utm-tracker-phone-cell"><select class="block min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-bind:class="isDuplicateCampaignPhone(campaignRow) ? 'border-rose-500 bg-rose-50 text-rose-700 ring-1 ring-rose-400 focus:border-rose-500 focus:ring-rose-500 dark:border-rose-500/70 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/40' : ''" x-bind:value="campaignRow.phone_key" x-bind:disabled="isPersisting" x-on:change="setCampaignPhone(campaignRow, $event.target.value)"><option value="">Без телефона</option><template x-for="phoneRow in state.phones" :key="phoneRow.key"><option x-bind:disabled="isPhoneOptionDisabled(phoneRow.key, campaignRow)" x-bind:selected="campaignRow.phone_key === phoneRow.key" x-bind:value="phoneRow.key" x-text="phoneOptionLabel(phoneRow.key, campaignRow)"></option></template></select><button type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-40" x-bind:class="isPhoneCopied(`tree-${campaignRow.key}`) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200' : ''" x-bind:disabled="isPersisting || ! campaignRow.phone_key" x-on:click="copySelectedPhone(campaignRow.phone_key, `tree-${campaignRow.key}`)" title="Скопировать телефон" aria-label="Скопировать телефон"><svg x-show="! isPhoneCopied(`tree-${campaignRow.key}`)" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 3.5A2.5 2.5 0 0 1 9.5 1h5A2.5 2.5 0 0 1 17 3.5v8A2.5 2.5 0 0 1 14.5 14h-5A2.5 2.5 0 0 1 7 11.5v-8Z" /><path d="M4.5 6A2.5 2.5 0 0 0 2 8.5v8A2.5 2.5 0 0 0 4.5 19h5a2.5 2.5 0 0 0 2.45-2H4.5a.5.5 0 0 1-.5-.5v-8a.5.5 0 0 1 .5-.5V6Z" /></svg><svg x-show="isPhoneCopied(`tree-${campaignRow.key}`)" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z" clip-rule="evenodd" /></svg></button></div>
                                                        <select class="block w-full rounded-md border-gray-300 px-2 py-1.5 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent dark:text-white" x-bind:value="campaignRow.cabinet || ''" x-bind:disabled="isPersisting" x-on:change="campaignRow.cabinet = normalizeCabinet($event.target.value); syncState()"><option value="" x-bind:selected="! campaignRow.cabinet">—</option><template x-for="(label, value) in cabinetOptions" :key="`campaign-cabinet-${campaignRow.key}-${value}`"><option x-bind:value="value" x-bind:selected="campaignRow.cabinet === value" x-text="label"></option></template></select>
                                                        <input type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent" x-bind:checked="!! campaignRow.open_booking_widget" x-bind:disabled="isPersisting" x-on:change="setCampaignWidget(campaignRow, $event.target.checked)" />
                                                        <span class="text-xs text-gray-400">—</span>
                                                        <input type="checkbox" class="utm-tracker-check h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent" x-bind:checked="!! campaignRow.vk_app_enabled" x-bind:disabled="isPersisting" x-on:change="setCampaignVkApp(campaignRow, $event.target.checked)" />
                                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-40" x-bind:class="isTrackingLinkCopied(campaignRow) ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200' : ''" x-on:click="copyTrackingLink(campaignRow)" x-bind:title="isTrackingLinkCopied(campaignRow) ? 'Скопировано' : 'Скопировать ссылку'" aria-label="Скопировать ссылку"><svg x-show="! isTrackingLinkCopied(campaignRow)" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.586 4.586a2 2 0 0 1 2.828 2.828l-3 3a2 2 0 0 1-2.828 0 .75.75 0 0 0-1.06 1.06 3.5 3.5 0 0 0 4.95 0l3-3a3.5 3.5 0 0 0-4.95-4.95l-1.5 1.5a.75.75 0 0 0 1.06 1.061l1.5-1.5Zm-5.172 10.828a2 2 0 0 1-2.828-2.828l3-3a2 2 0 0 1 2.828 0 .75.75 0 1 0 1.06-1.06 3.5 3.5 0 0 0-4.95 0l-3 3a3.5 3.5 0 0 0 4.95 4.95l1.5-1.5a.75.75 0 1 0-1.06-1.061l-1.5 1.5Z" clip-rule="evenodd" /></svg><svg x-show="isTrackingLinkCopied(campaignRow)" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-8 8a1 1 0 0 1-1.415 0l-4-4a1 1 0 1 1 1.414-1.415l3.293 3.294 7.294-7.293a1 1 0 0 1 1.408 0Z" clip-rule="evenodd" /></svg></button>
                                                        <span class="text-xs text-gray-700 dark:text-gray-100" x-text="ruleIsLaunched(campaignRow) ? formatDateTime(campaignRow.started_at) : '—'"></span>
                                                        <div class="utm-tracker-actions"><button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-60" x-bind:class="rowActionMode(campaignRow) ? '' : 'invisible'" x-bind:disabled="isPersisting || ! rowActionMode(campaignRow)" x-on:click="requestToggleCampaign(campaignRow)" x-bind:title="rowActionMode(campaignRow) === 'stop' ? 'Остановить' : 'Запустить'" x-bind:aria-label="rowActionMode(campaignRow) === 'stop' ? 'Остановить' : 'Запустить'"><svg x-show="rowActionMode(campaignRow) === 'stop'" class="h-4 w-4" viewBox="0 0 20 20" fill="#374151" aria-hidden="true"><path d="M6 6.75A.75.75 0 0 1 6.75 6h6.5a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-.75.75h-6.5A.75.75 0 0 1 6 13.25v-6.5Z" /></svg><svg x-show="rowActionMode(campaignRow) === 'launch'" class="h-4 w-4" viewBox="0 0 20 20" fill="#16a34a" aria-hidden="true"><path d="M6.25 4.7a.9.9 0 0 1 1.36-.77l7.25 4.55a.9.9 0 0 1 0 1.54l-7.25 4.55a.9.9 0 0 1-1.36-.77V4.7Z" /></svg></button><button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10 disabled:cursor-not-allowed disabled:opacity-60" x-bind:disabled="isPersisting" x-on:click="requestDeleteCampaignRow(campaignRow.key)" title="Удалить campaign" aria-label="Удалить campaign"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="#dc2626" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 2.5a1.75 1.75 0 0 0-1.75 1.75V5H4.75a.75.75 0 0 0 0 1.5h.443l.663 8.61A2.25 2.25 0 0 0 8.102 17.5h3.796a2.25 2.25 0 0 0 2.244-2.39l.663-8.61h.445a.75.75 0 0 0 0-1.5H13V4.25A1.75 1.75 0 0 0 11.25 2.5h-2.5ZM11.5 5V4.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V5h3Zm-2 3.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm-2.5.75a.75.75 0 0 1 1.5 0v4.25a.75.75 0 0 1-1.5 0V9Zm5-.75a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" /></svg></button></div>
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
