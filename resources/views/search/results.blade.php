<x-app-layout title="Результаты поиска по '{{ $search }}'" description="Результаты поиска по '{{ $search }}'">
    <section class="flex flex-col scroll-mt-16 scroll-smooth lg:scroll-mt-28 bg-white">
        <div class="py-10">
            <div class="container">
                <ol class="flex space-x-2 font-medium" itemscope
                    itemtype="https://schema.org/BreadcrumbList">
                    <li itemprop="itemListElement" itemscope
                        itemtype="https://schema.org/ListItem">
                        <a itemprop="item"
                           href="{{ home_route() }}"
                           class="text-interactive hover:text-interactive-hovered hover:underline">
                            <span itemprop="name">Главная</span>
                        </a>
                        <meta itemprop="position" content="1"/>
                    </li>
                    <li>/</li>
                    <li class="flex text-action-primary"
                        itemprop="itemListElement" itemscope
                        itemtype="https://schema.org/ListItem">
                        <span itemprop="name">Результаты поиска по <b>"{{ $search }}"</b></span>
                        <meta itemprop="position" content="2"/>
                    </li>
                </ol>
            </div>
        </div>
    </section>

    <section class="flex flex-col scroll-mt-16 scroll-smooth lg:scroll-mt-28">
        <div class="container py-10">
            <div class="mb-4">
                <form action="{{ city_route('search') }}" method="GET">
                    <div class="mt-2 relative w-full h-10">
                        <input type="text" name="q"
                               class="outline-none pl-14 text-interactive placeholder-interactive/50 text-sm w-1/3 h-full absolute left-0 top-0 border-none bg-white rounded-xl"
                               placeholder="Поиск по сайту..." value="{{ old('q', $search) }}" maxlength="100" autocomplete="off"
                               aria-label="Поиск по сайту">
                        <button class="absolute top-0 left-4 z-10 p-1 w-7 h-full block [&_svg]:fill-[#8794AC]"
                                type="submit">
                            <x-icon-search></x-icon-search>
                        </button>
                    </div>
                </form>
            </div>

            @if($errors->has('q'))
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                    Поисковый запрос не должен быть длиннее 100 символов.
                </div>
            @elseif($search === '')
                <div class="rounded-xl border border-surface-subdued bg-white p-4 text-sm text-interactive">
                    Введите поисковый запрос минимум из двух символов.
                </div>
            @elseif($results->count())
                <div class="mb-4">
                    <p class="text-xl font-medium">Результаты поиска по <span class="font-semibold">"{{ $search }}"</span></p>
                </div>
                <div class="search-results">
                    @foreach($results as $result)
                        <div class="mb-2 border p-4 bg-white">
                            <div class="card-body">
                                <div class="mb-2">
                                    <span class="inline-flex rounded-full bg-surface-subdued px-2 py-1 text-xs font-medium text-interactive">
                                        {{ $result->typeLabel }}
                                    </span>
                                </div>
                                <h5 class="text-2xl font-bold">
                                    <a href="{{ $result->getUrl() }}" class="hover:text-interactive-hovered hover:underline">{{ $result->title }}</a>
                                </h5>
                                @if($result->snippet)
                                    <p class="mt-2 text-sm text-interactive/80">{{ $result->snippet }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{ $results->links() }}
                </div>
            @else
                <div class="rounded-xl border border-surface-subdued bg-white p-4 text-sm text-interactive">
                    По запросу «{{ $search }}» ничего не найдено. Попробуйте изменить формулировку.
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
