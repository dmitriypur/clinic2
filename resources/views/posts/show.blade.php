@push('header-scripts')
    @if(isset($tag))
        <link rel="canonical" href="{{ url('tags/' . $tag->handle) }}">
    @elseif(isset($page->seo['canonical']) && $page->seo['canonical'] !== '')
        <link rel="canonical"
              href="{{ url($category->handle) }}{{ $page->seo['canonical'] != $category->handle ? '/'.$page->seo['canonical'] : '' }}">
    @else
        <link rel="canonical"
              href="{{ url($category->handle) }}{{ $page->handle != $category->handle ? '/'.$page->handle : '' }}">
    @endif

    @if(isset($category->seo['noindex']) && !!$category->seo['noindex'])
        <meta name="robots" content="noindex">
    @endif

    @if($category->header_scripts)
        {!! $category->header_scripts !!}
    @endif
@endpush

<x-app-layout title="{{ $title }}"
              description="{{ $description }}">
    @switch($page->type)
        @case (\App\Enums\PageType::Blog)
            <x-page.posts :page="$page" :filter="$filter" :tag="$tag" :posts="$posts" :handle="$category['handle']"
                          :categories="$categories"/>
            @foreach($page->blocks as $index => $block)
                <x-block
                    :block="$block"
                    breadcrumbsTitle="{{ $page->breadcrumbs_title }}"
                    pageTitle="{{ $page->title }}"
                    pageDescription="{{ $page->seo['description'] }}"
                />
            @endforeach
            @break

        @default
            @push('scripts')
                {!! Clinic::schema()->medicalScholarlyArticle($page) !!}
            @endpush
            <div class="flex flex-col">
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
                                <li itemprop="itemListElement" itemscope
                                    itemtype="https://schema.org/ListItem">
                                    <a itemprop="item"
                                       href="{{ city_route('pages.show', ['handle' => $page->category->handle]) }}"
                                       class="text-interactive hover:text-interactive-hovered hover:underline">
                                        <span itemprop="name">{{ $page->category->title }}</span>
                                    </a>
                                    <meta itemprop="position" content="2"/>
                                </li>
                                <li>/</li>
                                <li class="flex text-action-primary"
                                    itemprop="itemListElement" itemscope
                                    itemtype="https://schema.org/ListItem">
                                    <span itemprop="name">{{ $page->breadcrumbs_title }}</span>
                                    <meta itemprop="position" content="3"/>
                                </li>
                            </ol>
                        </div>
                    </div>
                </section>

                @foreach($page->blocks as $index => $block)
                    <x-block
                        :block="$block"
                        breadcrumbsTitle="{{ $page->breadcrumbs_title }}"
                        pageTitle="{{ $page->title }}"
                        pageDescription="{{ $page->seo['description'] }}"
                    />
                @endforeach
            </div>

    @endswitch
    <video-modal :open="videoUrl !== null" :video-url="videoUrl" @close="videoUrl = null"></video-modal>
</x-app-layout>
