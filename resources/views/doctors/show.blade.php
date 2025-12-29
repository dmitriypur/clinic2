@push('header-scripts')
    @if(isset($doctor->seo['canonical']) && $doctor->seo['canonical'] !== '')
        <link rel="canonical"
              href="{{ Clinic::relativeUrl(url($doctor->seo['canonical'])) }}">
    @else
        <link rel="canonical"
              href="{{ Clinic::relativeUrl(url()->current()) }}">
    @endif

    @if(isset($doctor->seo['noindex']) && !!$doctor->seo['noindex'])
        <meta name="robots" content="noindex">
    @endif

    @if($doctor->header_scripts)
        {!! $doctor->header_scripts !!}
    @endif

    {!! Clinic::schema()->physician($doctor) !!}
@endpush
<x-app-layout title="{{ $doctor->seo['title'] ?? $doctor->full_name }}" description="{{ $doctor->seo['description'] }}">
    <section class="bg-surface space-y-2.5 pb-4">
        <div class="py-10">
            <div class="container">
                <ol class="flex space-x-2 font-medium" itemscope itemtype="https://schema.org/BreadcrumbList">
                    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <a itemprop="item" href="{{ home_route() }}"
                            class="text-interactive hover:text-interactive-hovered hover:underline">
                            <span itemprop="name">Главная</span>
                        </a>
                        <meta itemprop="position" content="1" />
                    </li>
                    <li>/</li>
                    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <a itemprop="item" href="{{ city_route('pages.show', ['handle' => $doctorsPage->handle]) }}"
                            class="text-interactive hover:text-interactive-hovered hover:underline">
                            <span itemprop="name">{{ $doctorsPage->breadcrumbs_title }}</span>
                        </a>
                        <meta itemprop="position" content="1" />
                    </li>
                    <li>/</li>
                    <li class="flex text-action-primary" itemprop="itemListElement" itemscope
                        itemtype="https://schema.org/ListItem">
                        <span itemprop="name">{{ $doctor->full_name }}</span>
                        <meta itemprop="position" content="3" />
                    </li>
                </ol>
            </div>
        </div>

        <div class="container max-w-screen-sm md:max-w-screen-lg xl:max-w-screen-xl">
            <div class="md:-mx-8 md:px-4 md:py-8">
                <div class="flex flex-col md:flex-row gap-8 shadow-md md:shadow-none rounded-lg p-4">
                    <div class="space-y-6">
                        <div class="relative ">
                            <div
                                class="[&_img]:relative [&_img]:-top-10 md:[&_img]:top-0 relative h-60 md:h-auto md:w-60 [&_img]:w-full overflow-clip rounded">
                                {{ $doctor->avatar_image }}
                            </div>
                            <div
                                class="absolute -bottom-[15px] h-[30px] inset-x-0 z-20 flex justify-center md:hidden gap-5 px-4">
                                @if ($doctor->extra)
                                    <div
                                        class="flex md:hidden gap-1 items-center justify-center bg-surface-subdued rounded p-1 px-3">
                                        @for ($i = 0; $i < 5; $i++)
                                            <span>
                                                <x-icon.star class="w-5 h-5 fill-[#ffcc00]" />
                                            </span>
                                        @endfor
                                    </div>
                                @endif
                                @if ($doctor->actual_video_url)
                                    <div class="bg-surface-subdued rounded p-1 px-4 flex items-center">
                                        <button class="flex gap-2 items-center group cursor-pointer"
                                            @click="videoUrl='{{ $doctor->actual_video_url }}'">
                                            <span class="text-action-primary">
                                                <x-icon-play class="w-5 h-5 fill-current" />
                                            </span>
                                            <span
                                                class="pt-0.5 block font-medium text-interactive border-b border-b-transparent group-hover:border-b-interactive text-sm/none md:text-base/none">видеовизитка</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="space-y-6 flex flex-col justify-between">
                        <div class="space-y-6">
                            <div>
                                <p class="text-[13px] md:text-base">{{ $doctor->speciality }}</p>
                                <h1 class="text-[20px] md:text-4xl font-semibold text-left text-interactive">
                                    {{ $doctor->full_name }}</h1>
                            </div>
                            @if ($doctor->extra)
                                <div class="!mt-2 md:hidden">
                                    <div class="flex gap-1 text-[13px] md:text-base">
                                        <div class="text-interactive font-semibold">
                                            Стаж работы:
                                        </div>
                                        <div>{{ $doctor->extra['seniority'] }}</div>
                                    </div>
                                    <div class="flex gap-1 text-[13px] md:text-base">
                                        <div class="text-interactive font-semibold">
                                            Категория:
                                        </div>
                                        <div>{{ $doctor->extra['category'] }}</div>
                                    </div>
                                </div>
                            @endif

                            <div class="flex md:hidden gap-3 overflow-auto -mx-4 px-4">
                                <a href="#info"
                                    class="text-[13px]/5 rounded border border-action-primary text-action-primary whitespace-nowrap px-4 pt-2 pb-1.5">Информация
                                    о специалисте</a>
                                <a href="#skills"
                                    class="text-[13px]/5 rounded border border-action-primary text-action-primary whitespace-nowrap px-4 pt-2 pb-1.5">Профессиональные
                                    навыки</a>
                            </div>
                            @if ($doctor->extra)
                                <div class="hidden invisible md:visible md:flex gap-1.5 items-center">
                                    @for ($i = 0; $i < 5; $i++)
                                        <span>
                                            <x-icon.star class="w-6 h-6 fill-[#ffcc00]" />
                                        </span>
                                    @endfor
                                    <span
                                        class="hidden md:block pt-1 pl-2">{{ $doctor->extra['rating'] ?? '100% пациентов рекомендуют врача' }}</span>
                                </div>


                                @if ($doctor->extra['reviews'])
                                    <div class="space-y-5">
                                        <div class="font-semibold text-lg">
                                            Отзывы о специалисте:
                                        </div>
                                        <div class="flex gap-7">
                                            @foreach ($doctor->extra['reviews'] as $service)
                                                @if (empty($service['url']))
                                                    @continue
                                                @endif

                                                @php
                                                    $media = !empty($service['uuid'])
                                                        ? $doctor->getFirstMedia($service['uuid'])
                                                        : null;
                                                @endphp

                                                @if (!$media)
                                                    @continue
                                                @endif

                                                <a href="{{ $service['url'] }}" target="_blank" class="block">
                                                    {{ $media->img()->attributes(['class' => 'h-5 md:h-6']) }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <div>
                            <x-button-primary @click="showCallbackModal(null, 'otpravka-formy')"
                                onclick="ym(94302729,'reachGoal','shapka-forma-open')"
                                class="w-full md:w-auto">Записаться
                                на прием
                            </x-button-primary>
                        </div>
                    </div>
                </div>
                @if ($doctor->actual_video_url)
                    <div class="hidden md:block mt-4 px-4">
                        <button class="flex gap-2 items-center group cursor-pointer"
                            @click="videoUrl='{{ $doctor->actual_video_url }}'">
                            <span class="text-action-primary">
                                <x-icon-play class="w-6 h-6 fill-current" />
                            </span>
                            <span
                                class="font-medium text-interactive border-b border-b-transparent group-hover:border-b-interactive text-sm/none md:text-base/none">видеовизитка
                                врача</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($doctor->extra)
        <section class="bg-surface pt-4">
            <div class="container">
                <div class="xl:-mx-8 grid md:grid-cols-2 gap-8">
                    @if ($doctor->extra_information_filled)
                        <div class="md:bg-[#fbfcfd] md:rounded-[20px] md:p-6">
                            <div class="space-y-8">
                                <div id="info"
                                    class="scroll-mt-20 scroll-smooth space-y-8 bg-[#fbfcfd] md:bg-transparent shadow-md md:shadow-none rounded-lg md:rounded-none p-5 md:p-0">
                                    <div class="space-y-4">
                                        @if ($doctor->extra['seniority'])
                                            <x-doctor-info-block title="Стаж работы по специальности:"
                                                :value="$doctor->extra['seniority']" />
                                        @endif
                                        @if ($doctor->extra['category'])
                                            <x-doctor-info-block title="Категория:" :value="$doctor->extra['category']" />
                                        @endif
                                        @if ($doctor->extra['receives'])
                                            <x-doctor-info-block title="Ведёт приём:" :value="$doctor->extra['receives']" />
                                        @endif
                                    </div>

                                    @if ($doctor->extra['education'])
                                        <div class="flex gap-5 md:gap-2">
                                            <div class="w-36 md:w-[217px] font-semibold">
                                                Образование:
                                            </div>
                                            <div class="flex-1 space-y-8">
                                                @foreach ($doctor->extra['education'] as $institution)
                                                    <div>
                                                        <div class="flex items-start gap-4">
                                                            <span class="flex-none">
                                                                <x-icon.university
                                                                    class="hidden md:block stroke-current h-5 -ml-px relative" />
                                                            </span>
                                                            <span>{{ $institution['title'] }}</span>
                                                        </div>

                                                        <div class="space-y-4 mt-4 border-l md:ml-1.5 pl-3 md:pl-5">
                                                            @foreach ($institution['educational_institution'] as $item)
                                                                <div>
                                                                    <div class="text-xs md:text-base">
                                                                        {{ $item['year'] }}
                                                                    </div>
                                                                    <div class="text-sm md:text-base font-semibold">
                                                                        {{ $item['specialty'] }}</div>
                                                                    <div class="text-xs md:text-base">
                                                                        {{ $item['level'] }}
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if ($doctor->extra['professional_development'])
                                        <div class="flex gap-5 md:gap-2">
                                            <div class="w-36 md:w-[217px] font-semibold">
                                                Повышение квалификации:
                                            </div>
                                            <div class="flex-1 relative">
                                                <div class="absolute -left-px bg-[#fbfcfd] pb-2">
                                                    <x-icon.square-arrow-diagonal
                                                        class="hidden md:block [&_rect]:stroke-current [&_path]:fill-current w-4 pt-1" />
                                                </div>
                                                <div class="border-l md:ml-1.5 pl-5 space-y-4">
                                                    @foreach ($doctor->extra['professional_development'] as $item)
                                                        <div>
                                                            <div class="text-sm md:text-base font-semibold">
                                                                {{ $item['title'] }}</div>
                                                            <div class="text-xs md:text-base">{{ $item['year'] }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if (isset($doctor->extra['skills']) && count($doctor->extra['skills']))
                                    <div id="skills"
                                        class="scroll-mt-20 scroll-smooth flex flex-col md:flex-row gap-5 md:gap-2">
                                        <div class="md:w-[217px] font-semibold">
                                            Профессиональные навыки:
                                        </div>
                                        <div class="flex-1 relative">
                                            <div
                                                class="bg-[#fbfcfd] md:bg-transparent shadow-md md:shadow-none rounded-lg md:rounded-none p-5 md:p-0">
                                                <ol class="list-decimal list-inside space-y-4 md:pl-3">
                                                    @foreach ($doctor->extra['skills'] as $item)
                                                        <li>{{ $item }}</li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                @endisset
                        </div>
                @endif
            </div>
            @if ($doctor->hasMedia('documents'))
                <div class="md:bg-[#fbfcfd] md:rounded-[20px] md:p-6">
                    <div class="font-semibold mb-4">Документы,
                        подтверждающие квалификацию:
                    </div>
                    <div class="swiper doctor-documents-swiper hidden lg:block">
                        <div class="swiper-wrapper">
                            @foreach ($doctor->getMedia('documents')->sortByDesc('order_column')->chunk(3) as $chunk)
                                <div class="swiper-slide space-y-5 relative w-full">
                                    @foreach ($chunk as $slide)
                                        <a href="{{ $slide->getUrl() }}"
                                            class="glightbox block h-[275px] w-full rounded-2xl overflow-hidden relative">
                                            <picture itemscope itemtype="http://schema.org/ImageObject">
                                                <span itemprop="name" class="hidden">{{ $slide->title }}</span>
                                                {{ $slide->img()->attributes(['class' => 'object-cover object-center']) }}
                                            </picture>
                                        </a>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>

                        @if (count($doctor->getMedia('documents')) > 3)
                            <div class="flex justify-center">
                                <div class="relative inline-block">
                                    <div
                                        class="absolute right-full top-1/2 -translate-y-1/2 z-10 p-2 px-3 [&_>_.swiper-button-disabled]:opacity-0 hidden lg:block">
                                        <div
                                            class="doctor-documents-swiper-prev cursor-pointer hover:text-action-primary">
                                            <x-icon-angle-left class="stroke-current fill-none h-4" />
                                        </div>
                                    </div>

                                    <div
                                        class="absolute left-full top-1/2 -translate-y-1/2 z-10 p-2 px-3 [&_>_.swiper-button-disabled]:opacity-0 hidden lg:block">
                                        <div
                                            class="doctor-documents-swiper-next cursor-pointer hover:text-action-primary">
                                            <x-icon-angle-right class="stroke-current fill-none h-4" />
                                        </div>
                                    </div>
                                    <div
                                        class="relative inline-block doctor-documents-swiper-pagination space-x-2 text-center mt-6 lg:mb-6 [&_.swiper-pagination-bullet]:bg-transparent [&_.swiper-pagination-bullet]:opacity-100 [&_.swiper-pagination-bullet]:h-auto [&_.swiper-pagination-bullet]:w-auto [&_.swiper-pagination-bullet]:rounded-sm [&_.swiper-pagination-bullet-active]:border [&_.swiper-pagination-bullet-active] [&_.swiper-pagination-bullet-active]:px-3 [&_.swiper-pagination-bullet-active]:py-0.5 [&_.swiper-pagination-bullet-active]:font-semibold [&_.swiper-pagination-bullet-active]:border-gray-500">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div
                        class="swiper doctor-documents-mobile-swiper lg:hidden rounded-lg shadow-md p-4 bg-[#fbfcfd]">
                        <div class="swiper-wrapper">
                            @foreach ($doctor->getMedia('documents')->sortByDesc('order_column') as $slide)
                                <div class="swiper-slide !w-full">
                                    <div>
                                        <a href="{{ $slide->getUrl() }}"
                                            class="glightbox flex h-[275px] !w-full rounded-2xl overflow-hidden relative">
                                            {{ $slide->img()->attributes(['class' => 'object-cover object-center']) }}
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div
                            class="doctor-documents-mobile-swiper-pagination text-center mt-4 [&_>_.swiper-pagination-bullet]:bg-transparent [&_>_.swiper-pagination-bullet]:opacity-100 [&_>_.swiper-pagination-bullet]:border-2 [&_>_.swiper-pagination-bullet]:border-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:bg-action-primary [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet]:border-action-primary [&_>_.swiper-pagination-bullet:hover]:bg-icon-subdued [&_>_.swiper-pagination-bullet-active.swiper-pagination-bullet:hover]:border-icon-subdued">
                        </div>
                    </div>
                </div>
            @endif
        </div>
        </div>
    </section>
@endif

<video-modal :open="videoUrl !== null" :video-url="videoUrl" @close="videoUrl = null"></video-modal>
</x-app-layout>
