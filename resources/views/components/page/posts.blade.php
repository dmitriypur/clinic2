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
                    <span itemprop="name">{{  $tag ? $tag->title : $page->breadcrumbs_title }}</span>
                    <meta itemprop="position" content="2"/>
                </li>
            </ol>
        </div>
        <div class="container">
            <h1 class="font-semibold text-2xl md:text-4xl text-center text-heading mt-6 md:my-6 px-6 lg:px-0">
                {{ $tag ? $tag->title : $page->title }}
            </h1>
        </div>
    </div>

    <app-filter inline-template>
        <div ref="handle" role="{{ $handle }}">
            @if($categories->count() > 1)
                <div class="bg-white ">
                    <div class="container">
                        <div class="flex justify-between hidden">
                            @foreach($categories as $category)
                                <div
                                    class="w-1/2 text-center border-b {{ str_contains(url()->current(), $category->handle) ? 'border-action-primary' : 'border-interactive' }}">
                                    <a href="{{ city_route('pages.show', ['handle' => $category->handle]) }}"
                                       class="block pb-6 text-2xl font-medium {{ str_contains(url()->current(), $category->handle) ? 'text-action-primary' : 'text-interactive' }}">{{ $category->title }}</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="container">
                @if($filter !== null && $filter['tags']->count())
                    <div
                        class="relative hidden md:flex md:flex-wrap md:gap-4 md:mt-10 md:px-8 md:py-6 md:bg-interactive md:rounded-2xl">
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
                        <div @click="clearFilterTag"
                             :class="['py-1.5 px-3 text-sm font-medium inline-block rounded-md cursor-pointer border', !activeTags.length ? 'bg-white' : 'bg-white/70 border-interactive']">
                            Все
                        </div>
                        @foreach($filter['tags'] as $key => $tag)
                            <div @click="addTag({{ $key }})">
                                <filter-list inline-template
                                             :active="activeTags.includes({{ $key }})"
                                             :tag="{{ json_encode($tag) }}"
                                             :index="{{ $key }}"
                                             @toggle="toggleTag"
                                >
                                    <div @click="toggleTag" :class="['py-1.5 px-3 text-sm font-medium inline-block rounded-md cursor-pointer border', active ? 'bg-white' : 'bg-white/70 border-interactive']">
                                        {{ $tag }}
                                    </div>
                                </filter-list>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div v-if="!reviewArr.length"
                     class="flex flex-col md:grid md:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-14 py-10">
                    @foreach($posts as $post)
                        <x-blog-card :item="$post"></x-blog-card>
                    @endforeach
                    @if($posts->currentPage() < $posts->lastPage())
                        @if($posts->perPage() * $posts->currentPage() <= $posts->total())
                            <x-button-primary class="w-full lg:col-start-2 lg:col-span-1" @click="readMore">Открыть ещё
                            </x-button-primary>
                        @endif
                    @endif
                </div>
                <div v-else class="flex flex-col md:grid md:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-14 py-10">
                    <div v-if="reviewArr == 1" class="md:text-lg text-center">Ничего не
                        нашли...
                    </div>
                    <card-post v-else inline-template :post="post" v-for="post in reviewArr"></card-post>
                    <x-button-primary v-if="currentCountItems < totalCountItems" class="col-start-2 col-span-1"
                                      @click="readMore">Открыть ещё
                    </x-button-primary>
                </div>
            </div>
        </div>
    </app-filter>


</section>

