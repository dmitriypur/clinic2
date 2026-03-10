<section
    class="flex flex-col scroll-mt-16 scroll-smooth lg:scroll-mt-28 bg-surface pb-10">
    <div class="py-10">
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
        <h1 class="font-semibold text-2xl md:text-4xl text-center text-heading mb-6 px-6 lg:px-0">
            {{ $page->title }}
        </h1>
        <div class="content text-interactive">
            <div>{!! $page->body_html !!}</div>
        </div>
        @foreach ($doctors as $doctor)
            @push('scripts')
                {!! Clinic::schema()->physician($doctor) !!}
            @endpush
        @endforeach

        <infinite-doctors-list
            inline-template
            :initial-next-page-url='@json($doctors->nextPageUrl())'>
            <div @click="handleContainerClick">
                <div
                    ref="items"
                    data-doctors-items
                    class="space-y-4 lg:space-y-16 lg:divide-y lg:-mx-8 pt-8 lg:pt-0">
                    @include('components.page.partials.doctor-list-items', ['doctors' => $doctors])
                </div>

                <div class="sr-only" ref="pagination" data-doctors-pagination>
                    {{ $doctors->links() }}
                </div>

                <div v-if="isLoading" class="py-4 text-center text-sm text-interactive">
                    Загружаем врачей...
                </div>

                <div v-if="loadError" class="py-4 text-center">
                    <button
                        type="button"
                        class="text-action-primary hover:text-action-primary-hovered hover:underline"
                        @click="loadNextPage">
                        Не удалось загрузить следующую страницу. Повторить
                    </button>
                </div>

                <div v-if="!supportsIntersectionObserver && hasMorePages" class="py-4 text-center">
                    <button
                        type="button"
                        class="text-action-primary hover:text-action-primary-hovered hover:underline disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isLoading"
                        @click="loadNextPage">
                        Показать ещё
                    </button>
                </div>

                <div
                    v-show="hasMorePages"
                    ref="sentinel"
                    class="h-px w-full"
                    aria-hidden="true">
                </div>
            </div>
        </infinite-doctors-list>
    </div>
</section>

@foreach ($page->blocks as $index => $block)
    <x-block :block="$block" breadcrumbsTitle="{{ $page->breadcrumbs_title }}"
             pageTitle="{{ $page->title }}"
             pageDescription="{{ $page->seo['description'] }}"/>
@endforeach
