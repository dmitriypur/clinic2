<x-app-layout>
    <section class="py-10 lg:py-24 bg-surface">
        <div class="container">
            <h1 class="font-semibold text-2xl md:text-4xl text-heading mb-6 lg:mb-16">
                Карта сайта
            </h1>

            <div class="space-y-8">
                <div>
                    <h2 class="font-semibold text-xl md:text-2xl text-heading mb-4">Страницы</h2>
                    <ul class="list-disc ml-4">
                        @foreach($otherPages as $page)
                            <li>
                                <a href="{{ $page->handle }}"
                                   class="text-interactive underline hover:no-underline text-sm lg:text-base">{{ $page->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h2 class="font-semibold text-xl md:text-2xl text-heading mb-4">Услуги</h2>
                    <ul class="list-disc ml-4">
                        @foreach($servicesPages as $page)
                            <li>
                                <a href="{{ $page->handle }}"
                                   class="text-interactive underline hover:no-underline text-sm lg:text-base">{{ $page->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h2 class="font-semibold text-xl md:text-2xl text-heading mb-4">Статьи</h2>
                    <ul class="list-disc ml-4">
                        @foreach($postsPages as $page)
                            <li>
                                <a href="{{ $page->getUrl() }}"
                                   class="text-interactive underline hover:no-underline text-sm lg:text-base">{{ $page->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h2 class="font-semibold text-xl md:text-2xl text-heading mb-4">Врачи</h2>
                    <ul class="list-disc ml-4">
                        @foreach($doctors as $doctor)
                            <li>
                                <a href="{{ $doctor->url }}"
                                   class="text-interactive underline hover:no-underline text-sm lg:text-base">{{ $doctor->full_name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h2 class="font-semibold text-xl md:text-2xl text-heading mb-4">Теги</h2>
                    <ul class="list-disc ml-4">
                        @foreach($tags as $tag)
                            <li>
                                <a href="{{ city_route('tag.index', ['handle' => $tag->handle]) }}"
                                   class="text-interactive underline hover:no-underline text-sm lg:text-base">{{ $tag->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
