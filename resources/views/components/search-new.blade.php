<div is="search-live" inline-template {{ $attributes }} initial-query="{{ $query ?? '' }}" live-search-url="{{ route('live.search') }}">
    <div>
        <form action="{{ route('search') }}" method="GET">
            <div class="mt-2 relative w-full h-10">
                <input
                    type="text"
                    name="q"
                    class="outline-none pl-4 pr-14 text-interactive placeholder-interactive/50 text-sm absolute inset-0 border border-blue-500 rounded-xl"
                    placeholder="Поиск по сайту..." maxlength="100" autocomplete="off" aria-label="Поиск по сайту"
                    v-model="searchQuery"
                    @input="performSearch"
                    @focus="handleFocus"
                    @blur="handleBlur">
                <button class="absolute top-0 right-4 z-10 p-1 w-7 h-full block [&_svg]:fill-[#8794AC]" type="submit">
                    <x-icon-search></x-icon-search>
                </button>
            </div>
        </form>
        <div class="w-full absolute bg-white z-10 py-2 shadow-lg" v-if="showResults" @mousedown.prevent="cancelHideResults">
            <p v-if="isLoading" class="px-3 py-2 text-sm text-interactive/70" role="status">Ищем…</p>
            <p v-else-if="searchError" class="px-3 py-2 text-sm text-red-700" role="alert">Не удалось выполнить поиск. Попробуйте ещё раз.</p>
            <p v-else-if="hasSearched && searchResults.length === 0" class="px-3 py-2 text-sm text-interactive/70">Ничего не найдено.</p>
            <ul v-else>
                <li v-for="result in searchResults" :key="result.key">
                    <a :href="getResultLink(result)" class="block px-3 py-2 text-sm font-medium hover:bg-[#FFE5CC]">
                        <span class="mr-2 inline-flex rounded-full bg-surface-subdued px-2 py-0.5 text-xs font-medium text-interactive">@{{ result.type_label }}</span>
                        <span>@{{ result.title }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
