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
        <div class="space-y-4 lg:space-y-16 lg:divide-y lg:-mx-8 pt-8 lg:pt-0">
            @foreach ($doctors as $doctor)
                @push('scripts')
                    {!! Clinic::schema()->physician($doctor) !!}
                @endpush
                <div
                    class="p-4 lg:pt-16 lg:px-8 shadow lg:shadow-none rounded-lg">
                    <div class="flex gap-3 lg:gap-9">
                        <div class="space-y-2 max-w-[138px] lg:max-w-60">
                            <a href="{{ $doctor->url }}"
                               class="rounded lg:rounded-none block overflow-clip">
                                {{ $doctor->avatar_image }}
                            </a>
                            <div
                                class="flex lg:hidden gap-1 items-center justify-center bg-surface-subdued rounded p-1">
                                @for ($i = 0; $i < 5; $i++)
                                    <span>
                                        <x-icon.star
                                            class="w-5 h-5 fill-[#ffcc00]"/>
                                    </span>
                                @endfor
                            </div>
                        </div>
                        <div class="flex flex-col justify-between">
                            <div
                                class="flex flex-col justify-between lg:justify-normal space-y-4 h-full lg:h-auto">
                                <div class="space-y-1 lg:space-y-3">
                                    <div class="lg:pb-2">
                                        <p class="text-[13px]/4 lg:text-base pb-2 lg:pb-0">{{ $doctor->speciality }}</p>
                                        <a href="{{ $doctor->url }}"
                                           class="text-lg/6 lg:text-2xl font-semibold text-left text-interactive hover:underline w-min block lg:w-auto">{{ $doctor->full_name }}</a>
                                    </div>

                                    @if ($doctor->extra)
                                        <div class="pt-1 lg:pt-0">
                                            <div
                                                class="flex gap-1 text-[13px] lg:text-base">
                                                <div
                                                    class="text-interactive font-semibold">
                                                    Стаж работы:
                                                </div>
                                                <div>{{ $doctor->extra['seniority'] }}</div>
                                            </div>
                                            <div
                                                class="flex gap-1 text-[13px] lg:text-base">
                                                <div
                                                    class="text-interactive font-semibold">
                                                    Категория:
                                                </div>
                                                <div>{{ $doctor->extra['category'] }}</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if ($doctor->actual_video_url)
                                    <div class="pb-0.5">
                                        <button
                                            class="flex gap-2 items-center group cursor-pointer"
                                            @click="videoUrl='{{ $doctor->actual_video_url }}'">
                                            <span class="text-action-primary">
                                                <x-icon-play
                                                    class="w-6 h-6 fill-current"/>
                                            </span>
                                            <span
                                                class="font-medium text-interactive border-b border-b-transparent group-hover:border-b-interactive text-sm/none lg:text-base/none">видеовизитка</span>
                                        </button>
                                    </div>
                                @endif

                                <div
                                    class="hidden invisible lg:visible lg:flex gap-1.5 items-center">
                                    @for ($i = 0; $i < 5; $i++)
                                        <span>
                                            <x-icon.star
                                                class="w-6 h-6 fill-[#ffcc00]"/>
                                        </span>
                                    @endfor
                                    <span
                                        class="hidden lg:block pt-1 pl-2">{{ $doctor->extra['rating'] ?? '100% пациентов рекомендуют врача' }}</span>
                                </div>
                            </div>
                            <div class="hidden lg:block">
                                <x-button-primary
                                    @click="showCallbackModal(null, 'otpravka-formy')"
                                    onclick="ym(94302729,'reachGoal','shapka-forma-open')">
                                    Записаться на прием
                                </x-button-primary>
                            </div>
                        </div>
                    </div>
                    <div class="lg:hidden mt-4">
                        <x-button-primary
                            @click="showCallbackModal(null, 'otpravka-formy')"
                            onclick="ym(94302729,'reachGoal','shapka-forma-open')"
                            class="w-full">Записаться на прием
                        </x-button-primary>
                    </div>
                </div>
            @endforeach

            {{ $doctors->links() }}
        </div>
    </div>
</section>

@foreach ($page->blocks as $index => $block)
    <x-block :block="$block" breadcrumbsTitle="{{ $page->breadcrumbs_title }}"
             pageTitle="{{ $page->title }}"
             pageDescription="{{ $page->seo['description'] }}"/>
@endforeach
