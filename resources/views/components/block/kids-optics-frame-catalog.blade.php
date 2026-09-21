@php
    $initialFrames = array_slice($frames, 0, 4);
    $deferredFrames = array_slice($frames, 4);
@endphp

<frame-catalog inline-template>
    <div class="container">
        <div class="mx-auto max-w-[1248px]">
        <h2 class="text-center text-[28px] font-semibold leading-[1.2] text-heading md:text-[34px]">
            {{ $catalogTitle }}
        </h2>

        <div class="mt-5 flex flex-col gap-4 lg:mt-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="grid grid-cols-4 gap-4 lg:flex">
                @foreach($ageFilters as $ageValue => $ageFilter)
                    <button
                        type="button"
                        class="flex h-[49px] min-w-0 items-center justify-center rounded-full border-[6px] border-[#1f3462]/[0.12] bg-white px-1 text-center text-sm font-bold leading-[1.4] text-heading lg:w-[120px] lg:px-2 lg:text-base lg:leading-[1.3]"
                        :class="{ 'border-[#1f3462]/[0.24] bg-[#eff5ff]': activeAge === '{{ $ageValue }}' }"
                        :aria-pressed="activeAge === '{{ $ageValue }}'"
                        @click="toggleAge('{{ $ageValue }}')"
                        data-frame-catalog-age-filter
                    >
                        {{ $ageFilter }}
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-2 gap-4 lg:flex">
                @foreach(['boy' => 'Для мальчиков', 'girl' => 'Для девочек'] as $gender => $label)
                    <button
                        type="button"
                        class="relative flex h-[49px] min-w-0 items-center overflow-hidden rounded-full border-[6px] border-[#1f3462]/[0.12] bg-white pl-[54px] pr-2 text-left text-sm font-bold leading-[1.4] text-heading lg:w-[220px] lg:pl-[73px] lg:pr-2 lg:text-base lg:leading-[1.3]"
                        :class="{ 'border-[#1f3462]/[0.24] bg-[#eff5ff]': activeGender === '{{ $gender }}' }"
                        :aria-pressed="activeGender === '{{ $gender }}'"
                        @click="toggleGender('{{ $gender }}')"
                        data-frame-catalog-gender-filter="{{ $gender }}"
                    >
                        <span class="absolute inset-y-0 left-0 w-10 overflow-hidden lg:w-[55px]" aria-hidden="true">
                            <img
                                src="{{ asset("images/kids-optics/frame-catalog/filter-{$gender}.png") }}"
                                class="h-[49px] max-w-none object-cover {{ $gender === 'girl' ? 'w-[55px] -translate-x-2' : 'w-[55px] -translate-x-1' }}"
                                alt=""
                                width="55"
                                height="49"
                                loading="lazy"
                                decoding="async"
                            >
                        </span>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div ref="items" class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[repeat(3,400px)] xl:justify-start">
            @foreach($initialFrames as $frame)
                @include('components.block.partials.kids-optics-frame-card', ['frame' => $frame])
            @endforeach
        </div>

        <div v-cloak v-if="!hasResults" class="mt-10 rounded-[24px] bg-white px-6 py-10 text-center text-heading">
            <p class="text-lg font-semibold">По выбранным параметрам оправ пока нет</p>
            <button type="button" class="mt-3 text-sm font-semibold underline" @click="resetFilters">
                Сбросить фильтры
            </button>
        </div>

        @if($deferredFrames)
            <textarea ref="deferredItems" class="hidden" aria-hidden="true" tabindex="-1">
                @foreach($deferredFrames as $frame)
                    @include('components.block.partials.kids-optics-frame-card', ['frame' => $frame])
                @endforeach
            </textarea>
        @endif

        <div v-cloak v-if="hasMoreFrames" class="mt-8 flex justify-center md:mt-10">
            <button
                type="button"
                class="rounded-[10px] border border-heading px-[30px] py-4 text-base font-semibold leading-[1.22] text-heading transition-colors hover:bg-white disabled:cursor-wait disabled:opacity-60"
                data-frame-catalog-show-more
                :disabled="isLoading"
                @click="showMore"
            >
                <span v-if="isLoading">Загружаем...</span>
                <template v-else>
                    <span class="md:hidden">{{ $showMoreMobileText }} (@{{ nextBatchCount }})</span>
                    <span class="hidden md:inline">{{ $showMoreDesktopText }} (@{{ nextBatchCount }})</span>
                </template>
            </button>
        </div>
    </div>
    </div>
</frame-catalog>
