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
