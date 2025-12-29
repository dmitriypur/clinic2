@push('header-scripts')
    @if(isset($page->seo['canonical']) && $page->seo['canonical'] !== '')
        <link rel="canonical"
              href="{{ url($page->seo['canonical']) }}">
    @else
        <link rel="canonical"
              href="{{ url()->current() }}">
    @endif

    @if(isset($page->seo['noindex']) && !!$page->seo['noindex'])
        <meta name="robots" content="noindex">
    @endif

    @if($page->header_scripts)
        {!! $page->header_scripts !!}
    @endif
@endpush

<x-app-layout title="{{ $page->seo['title'] ?? $page->title }}" description="{{ $page->seo['description'] }}">
    @switch($page->type)
        @case (\App\Enums\PageType::Doctors)
            <x-page.doctors :page="$page" :doctors="$doctors"/>
            @break

        @case (\App\Enums\PageType::Reviews)
            <x-page.reviews :page="$page" :filter="$filter" :reviews="$reviews"/>
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
            @if(in_array($page->type, [\App\Enums\PageType::Posts, \App\Enums\PageType::Services]))
                @push('scripts')
                    {!! Clinic::schema()->medicalScholarlyArticle($page) !!}
                @endpush
            @endif

            @foreach($page->blocks as $index => $block)
                <x-block
                    :block="$block"
                    breadcrumbsTitle="{{ $page->breadcrumbs_title }}"
                    pageTitle="{{ $page->title }}"
                    pageDescription="{{ $page->seo['description'] }}"
                />
            @endforeach

            @if(count($services) || $page->body_html)
                <section class="py-10 lg:pb-24 lg:pt-6 bg-surface">
                    <div class="container">
                        @if(isset($services) && count($services))
                            <div class="grid lg:grid-cols-2 gap-4 lg:gap-8">
                                @foreach($services->chunk(round(count($services)/2)) as $chunk)
                                    <div class="space-y-4 lg:space-y-8">
                                        @foreach($chunk as $service)
                                            <div is="service-card"
                                                 inline-template
                                                 :uuid="'{{ $service->uuid }}'">
                                                <div
                                                    class="rounded-lg border pb-0 max-w-full scroll-mt-20 scroll-smooth lg:scroll-mt-32"
                                                    id="{{ $service->uuid }}">
                                                    <div
                                                        :class="mainCardClassName"
                                                        @click="toggle">
                                                        <div
                                                            class="w-16 h-16 lg:w-20 lg:h-20 lg:p-1 rounded-full overflow-hidden flex-none">
                                                            <picture
                                                                itemscope
                                                                itemtype="http://schema.org/ImageObject">
                                                        <span itemprop="name"
                                                              class="hidden">{{ $service->title }}</span>
                                                                {{ $service->responsive_image }}
                                                            </picture>
                                                        </div>
                                                        <div
                                                            class="flex-auto">
                                                            <h2 class="font-medium lg:text-2xl text-interactive leading-none lg:leading-normal">
                <span
                    class="box-decoration-clone border-b border-transparent group-hover:border-interactive">
                {{ $service->title }}
                </span>
                                                            </h2>
                                                        </div>
                                                        <div
                                                            class="p-4 -mr-4 text-interactive">
                                                                <span
                                                                    v-if="active"><x-icon-minus/></span>
                                                            <span v-else><x-icon-plus/></span>
                                                        </div>
                                                    </div>
                                                    <div :class="className">
                                                        @foreach($service->children as $child)
                                                            @push('scripts')
                                                                {!! Clinic::schema()->offer($child->title, $child->current_price?->price ?? 0) !!}
                                                            @endpush
                                                            <div
                                                                class="flex justify-between items-start border-b py-4 last:border-transparent space-x-4">
                                                                <p>{{ $child->title }}</p>
                                                                <div class="font-medium whitespace-nowrap text-right">
                                                                    @if($child->current_price?->old_price)
                                                                        <span class="block text-sm text-gray-400 line-through decoration-red-500/50">
                                                                            {{ number_format($child->current_price->old_price, 0, '.', ' ') }} руб.
                                                                        </span>
                                                                    @endif
                                                                    <div>
                                                                        @if($child->current_price?->price_from)
                                                                            от
                                                                        @endif
                                                                        {{ number_format($child->current_price?->price ?? 0, 0, '.', ' ') }}
                                                                        руб.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($page->body_html && $page->type !== \App\Enums\PageType::Posts)
                            <div is="text-accordion" inline-template
                                 :count="{{ $page->paragraph_count}}">
                                <div>
                                    <div
                                        class="content [&_a]:text-interactive [&_a]:underline [&_a:hover]:no-underline [&_a:visited]:text-purple-800 mt-6 lg:mt-8">
                                        <div class="space-y-2">
                                            {!! $page->first_paragraph !!}
                                        </div>
                                        <div
                                            :class="className">
                                            <div class="space-y-2">

                                                {!! $page->after_first_paragraph !!}
                                            </div>
                                        </div>
                                    </div>
                                    @if ($page->paragraph_count > 1)
                                        <a href="javascript:;"
                                           class="py-1 block text-action-primary hover:text-action-primary-hovered hover:underline"
                                           @click="toggle">
                                                <span
                                                    v-if="!open">Читать далее</span>
                                            <span v-else>Скрыть</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @endif
    @endswitch
    <video-modal :open="videoUrl !== null" :video-url="videoUrl" @close="videoUrl = null"></video-modal>
</x-app-layout>
