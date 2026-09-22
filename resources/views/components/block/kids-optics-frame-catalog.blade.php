<frame-catalog
    inline-template
    endpoint="{{ $catalogEndpoint }}"
    :initial-total="{{ $totalFrames }}"
    :initial-count="{{ count($frames) }}"
>
    <div class="container">
        <div class="mx-auto max-w-7xl">
            <h2 class="text-center text-3xl font-semibold leading-tight text-heading md:text-4xl">
                {{ $catalogTitle }}
            </h2>

            <div class="mt-5 flex flex-col gap-4 lg:mt-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="grid grid-cols-4 gap-1 lg:flex">
                    @foreach($ageFilters as $ageId => $ageFilter)
                        <button
                            type="button"
                            class="flex h-12 min-w-0 items-center justify-center rounded-full border-4 border-[#1f3462]/[0.12] bg-white px-1 text-center text-xs font-bold leading-snug text-heading lg:w-32 lg:px-2 lg:text-base lg:leading-snug"
                            :class="{ 'border-[#1f3462]/[0.24] bg-[#eff5ff]': activeAges.includes({{ $ageId }}) }"
                            :aria-pressed="activeAges.includes({{ $ageId }})"
                            @click="toggleAge({{ $ageId }})"
                            data-frame-catalog-age-filter="{{ $ageId }}"
                        >
                            {{ $ageFilter }}
                        </button>
                    @endforeach
                </div>

                <div class="grid grid-cols-2 gap-1 lg:flex">
                    @foreach(\App\Enums\FrameGender::options() as $gender => $label)
                        <button
                            type="button"
                            class="relative flex h-14 min-w-0 items-center justify-center overflow-hidden rounded-full border-4 border-[#1f3462]/[0.12] bg-white pl-12 pr-1 text-left text-sm font-bold leading-snug text-heading lg:w-56 lg:pl-16 lg:pr-2 lg:text-base lg:leading-snug"
                            :class="{ 'border-[#1f3462]/[0.24] bg-[#eff5ff]': activeGenders.includes('{{ $gender }}') }"
                            :aria-pressed="activeGenders.includes('{{ $gender }}')"
                            @click="toggleGender('{{ $gender }}')"
                            data-frame-catalog-gender-filter="{{ $gender }}"
                        >
                            <span class="absolute inset-y-0 left-0 w-10  lg:w-14" aria-hidden="true">
                                <img
                                    src="{{ asset("images/kids-optics/frame-catalog/filter-{$gender}.png") }}"
                                    class="h-full max-w-none object-contain {{ $gender === 'girl' ? 'w-auto -translate-x-1 md:translate-x-0' : 'w-auto -translate-x-1  md:translate-x-0' }}"
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

            <div ref="items" data-frame-catalog-list class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[repeat(3,400px)] xl:justify-start">
                @foreach($frames as $frame)
                    @include('components.block.partials.kids-optics-frame-card', ['frame' => $frame])
                @endforeach
            </div>

            <div v-cloak v-if="!isLoading && total === 0" class="mt-10 rounded-3xl bg-white px-6 py-10 text-center text-heading">
                <p class="text-lg font-semibold">По выбранным параметрам оправ пока нет</p>
                <button type="button" class="mt-3 text-sm font-semibold underline" @click="resetFilters">
                    Сбросить фильтры
                </button>
            </div>

            <p v-cloak v-if="errorMessage" class="mt-6 text-center text-sm text-red-700" role="status">
                @{{ errorMessage }}
            </p>

            <div v-cloak v-if="hasMoreFrames" class="mt-8 flex justify-center md:mt-10">
                <button
                    type="button"
                    class="rounded-lg border border-heading px-8 py-4 text-base font-semibold leading-tight text-heading transition-colors hover:bg-white disabled:cursor-wait disabled:opacity-60"
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
