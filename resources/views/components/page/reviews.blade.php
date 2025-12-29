<section
    class="flex flex-col scroll-mt-16 scroll-smooth lg:scroll-mt-28 bg-surface-subdued pb-10">
    <div class="py-10 bg-white">
        <div class="container">
            <ol class="flex space-x-2 font-medium" itemscope
                itemtype="https://schema.org/BreadcrumbList">
                <li itemprop="itemListElement" itemscope
                    itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="{{ home_route() }}"
                       class="text-interactive hover:text-interactive-hovered hover:underline">
                        <span itemprop="name">Главная</span>
                    </a>
                    <meta itemprop="position" content="1"/>
                </li>
                <li>/</li>
                <li class="flex text-action-primary" itemprop="itemListElement"
                    itemscope
                    itemtype="https://schema.org/ListItem">
                    <span itemprop="name">{{ $page->breadcrumbs_title }}</span>
                    <meta itemprop="position" content="2"/>
                </li>
            </ol>
        </div>
    </div>
    <div class="container">
        <h1 class="font-semibold text-2xl md:text-4xl text-center text-heading mt-6 md:my-6 px-6 lg:px-0">
            {{ $page->title }}
        </h1>
    </div>
    <app-filter inline-template>
        <div ref="handle" role="reviews">
            <div class="md:bg-white w-full py-4 md:py-10 mb-2 md:mb-6 relative">
                <div v-if="loading" class="flex justify-center items-center absolute inset-0 bg-white/80 z-20">
                    <svg
                        class="mr-3 -ml-1 size-8 animate-spin text-action-primary"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                </div>

                <div :class="['container mx-auto w-[92%] md:w-auto px-2 pt-3 rounded-lg flex gap-4 md:block md:bg-transparent', isVisible ? 'bg-blue-gray' : '']">
                    <p class="hidden md:block md:text-3xl md:text-center md:font-semibold md:mb-6">Выберите
                        специалиста</p>

                    <div class="w-1/2 md:w-full md:bg-blue-gray md:rounded-2xl md:pt-24 md:pb-5 md:px-28">
                        <div class="md:grid md:grid-cols-{{ count($filter['doctors']) }} md:gap-6 lg:gap-14">
                            <div @click="toggleVisible"
                                 :class="['md:hidden flex items-center gap-3 rounded-lg text-sm font-medium mb-2', isVisible ? 'bg-white border border-interactive' : 'bg-white/45']">
                                <div
                                    :class="['w-9 h-9 rounded-md flex items-center justify-center', isVisible ? 'bg-interactive [&_svg]:fill-white' : 'bg-white [&_svg]:fill-gray-300']">
                                    <x-icon-filter width="17"></x-icon-filter>
                                </div>
                                По врачам
                            </div>
                            @foreach ($filter['doctors'] as $id => $doctor)
                                <filter-list inline-template @add-resource="addDoctor" v-show="isVisible">
                                    <div class="h-full w-auto cursor-pointer mb-2 md:mb-0"
                                         @click="toggle({{ $doctor->id }})">
                                        <div :class="classDoctor">
                                            {{ $doctor->avatar_image }}
                                            <div
                                                :class="['border border-interactive md:border-0 text-xs font-medium md:text-base md:mt-auto w-full flex md:gap-1 items-center justify-center h-6 md:h-11 rounded-md', isActive ? 'bg-white md:bg-action-primary md:text-white' : 'md:bg-white bg-white/50']">
                                                        <span
                                                            class="hidden lg:block">Врач:</span>{{ $doctor->doctorInitials }}
                                            </div>
                                        </div>
                                    </div>
                                </filter-list>
                            @endforeach
                        </div>
                    </div>

                    <div class="w-1/2 md:w-full md:bg-blue-gray md:rounded-2xl md:pt-5 md:mt-20 md:relative">
                        <a href="{{ city_route('pages.show', ['handle' => 'reviews']) }}"
                           class="hidden md:block md:rounded-lg md:text-center py-2 px-6 font-semibold cursor-pointer bg-white shadow-interactive-s absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2">Сбросить
                            фильтры</a>
                        <div class="hidden md:flex md:justify-between md:px-28 md:mb-3.5">
                            <x-button-darkblue>Поиск по источнику отзыва:</x-button-darkblue>
                        </div>
                        <div class="md:flex md:flex-wrap md:gap-4 lg:justify-between md:px-28">
                            <div @click="toggleVisible"
                                 :class="['md:hidden flex items-center gap-3 rounded-lg text-sm font-medium mb-2', isVisible ? 'bg-white border border-interactive' : 'bg-white/45']">
                                <div
                                    :class="['w-9 h-9 rounded-md flex items-center justify-center', isVisible ? 'bg-interactive [&_svg]:fill-white' : 'bg-white [&_svg]:fill-gray-300']">
                                    <x-icon-filter width="17"></x-icon-filter>
                                </div>
                                По источнику
                            </div>
                            @foreach($filter['resources'][1] as $key => $value)
                                <filter-list inline-template @add-resource="addResource" v-show="isVisible">
                                    <div :class="className" @click="toggle({{$key}})" ref="test">
                                        <div
                                            class="md:w-auto md:min-w-10 h-6 md:h-10 px-2 md:py-0 md:px-1 md:rounded-md md:bg-surface-subdued flex items-center justify-center">
                                            <img src="{{asset($filter['resources'][0][$key] ?? '')}}"
                                                 alt="Иконка {{ $value }}" class="max-w-4 md:max-w-full">
                                        </div>
                                        <p class="text-xs font-medium md:text-base">{{ $value ?? '' }}</p>
                                    </div>
                                </filter-list>
                            @endforeach
                        </div>
                        <div
                            class="hidden md:flex md:flex-wrap md:gap-4 md:mt-10 md:px-28 md:pt-16 md:pb-5 md:bg-white/50 md:rounded-2xl">
                            <x-button-darkblue>Поиск по услуге:</x-button-darkblue>
                            @foreach($filter['services'] as $key => $service)
                                <div @click="addService({{ $key }})">
                                    <filter-list inline-template>
                                        <div @click="toggle"
                                             :class="['py-1.5 px-3 text-sm font-medium inline-block rounded-md cursor-pointer border', isActive ? 'bg-white border-interactive' : 'bg-interactive/15']">{{ $service }}</div>
                                    </filter-list>
                                </div>

                            @endforeach
                        </div>
                    </div>
                </div>
                <a href="{{ city_route('pages.show', ['handle' => 'reviews']) }}" v-if="isVisible"
                   class="w-full block mx-auto mt-6 max-w-[92%] rounded-lg text-center py-2.5 px-6 font-semibold cursor-pointer bg-white md:hidden">Сбросить
                    фильтры</a>
            </div>

            <div class="container">
                <div class="md:grid md:grid-cols-2 lg:grid-cols-3 gap-7" v-if="!reviewArr.length">
                    @foreach($reviews as $review)
                        <x-review-card :page="$page" :review="$review" class="max-w-1/3 gap-10"></x-review-card>
                    @endforeach
                    @if($reviews->currentPage() < $reviews->lastPage())
                        @if($reviews->perPage() * $reviews->currentPage() <= $reviews->total())
                            <x-button-primary class="w-full lg:col-start-2 lg:col-span-1" @click="readMore">Открыть
                                ещё
                            </x-button-primary>
                        @endif
                    @endif
                </div>
                <div v-else class="md:grid md:grid-cols-2 lg:grid-cols-3 gap-7">
                    <div v-if="reviewArr == 1" class="md:col-start-2 md:col-span-1 md:text-lg text-center">Ничего не
                        нашли...
                    </div>
                    <card v-else is="card" inline-template :review="item" v-for="item in reviewArr"></card>
                    <x-button-primary v-if="currentCountItems < totalCountItems" class="col-start-2 col-span-1"
                                      @click="readMore">Открыть ещё
                    </x-button-primary>
                </div>
            </div>
        </div>
    </app-filter>
</section>

