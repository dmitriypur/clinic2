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
                <form action="{{ route('search') }}" method="GET">
                    <div class="mt-2 relative w-full h-10">
                        <input type="text" name="q"
                               class="outline-none pl-14 text-interactive placeholder-interactive/50 text-sm w-1/3 h-full absolute left-0 top-0 border-none bg-white rounded-xl"
                               placeholder="Поиск по сайту..." value="{{ $search ?? '' }}">
                        <button class="absolute top-0 left-4 z-10 p-1 w-7 h-full block [&_svg]:fill-[#8794AC]"
                                type="submit">
                            <x-icon-search></x-icon-search>
                        </button>
                    </div>
                </form>
            </div>

            @if(isset($results) && count($results))
                <div class="mb-4">
                    <p class="text-xl font-medium">Результаты поиска по <span class="font-semibold">"{{ $search }}"</span></p>
                </div>
                <div class="search-results">
                    @foreach($results as $post)
                        <div class="mb-2 border p-4 bg-white">
                            <div class="card-body">
                                <h5 class="text-2xl font-bold"><a href="{{ city_route('pages.show', ['handle' => $post->handle]) }}">{{ $post->title }}</a>
                                </h5>
                            </div>
                        </div>
                    @endforeach

                    {{ $results->links() }}
                </div>
            @elseif(isset($search))
                <div class="alert alert-info">No results found for "{{ $search }}"</div>
            @endif
        </div>
    </section>
</x-app-layout>
