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
                            <th class="w-[36%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Телефон</th>
                            <th class="w-[12%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Активен</th>
                            <th class="w-[16%] py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Статус</th>
                            <th class="py-2 pr-3 text-left font-medium text-gray-500 dark:text-gray-400">Использование</th>
                            <th class="w-24 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Действия</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-if="state.phones.length === 0">
                            <tr>
                                <td colspan="5" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
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
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-transparent"
                                        x-model="phoneRow.is_active"
                                        x-bind:disabled="isPersisting"
                                        x-on:change="syncState()"
                                        title="Активен"
                                        aria-label="Активен"
                                    />
                                </td>

                                <td class="py-2 pr-3 align-top">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                        x-bind:class="phoneRow.is_active === false ? 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300' : (isDuplicatePhoneInActiveCampaigns(phoneRow.key) ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-200' : (isPhoneBusyForDisplay(phoneRow.key) ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200'))"
                                        x-text="phoneRow.is_active === false ? 'неактивен' : (isDuplicatePhoneInActiveCampaigns(phoneRow.key) ? 'дубль' : (isPhoneBusyForDisplay(phoneRow.key) ? 'занят' : 'свободен'))"
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
