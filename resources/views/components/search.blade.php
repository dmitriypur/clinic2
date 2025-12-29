<div is="search-live" inline-template class="mt-2 relative">
    <div>
        <form action="{{ route('search') }}" method="GET" @submit.prevent="submitSearch">
            <div class="mt-2 relative w-full h-10">
                <input
                    type="text"
                    name="q"
                    class="outline-none pl-14 text-interactive placeholder-interactive/50 text-sm w-full h-full absolute left-0 top-0 border-none bg-surface-subdued rounded-xl"
                    placeholder="Поиск по сайту..." value="{{ $query ?? '' }}"
                    v-model="searchQuery"
                    @input="performSearch">
                <button class="absolute top-0 left-4 z-10 p-1 w-7 h-full block [&_svg]:fill-[#8794AC]" type="submit">
                    <x-icon-search></x-icon-search>
                </button>
            </div>
        </form>
        <div class="w-full absolute bg-white z-10 py-2 shadow-lg" v-show="showResults && searchResults.length > 0">
            <ul>
                <li v-for="result in searchResults" :key="result.id">
                    <a :href="getResultLink(result)" @mousedown.prevent="handleResultClick(result)" class="block text-sm font-medium px-2 py-1 hover:bg-[#FFE5CC]">
                        @{{ result.title }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
