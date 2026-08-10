@push('header-scripts')
    @php
        $rawCanonical = data_get($doctor->seo, 'canonical');

        if ($rawCanonical && filter_var($rawCanonical, FILTER_VALIDATE_URL)) {
            $canonicalHref = $rawCanonical;
        } elseif ($rawCanonical) {
            $canonicalPath = ltrim((string) $rawCanonical, '/');

            if ($canonicalPath !== '' && !str_starts_with($canonicalPath, 'doctors/')) {
                $canonicalPath = 'doctors/' . $canonicalPath;
            }

            $canonicalHref = city_url('/' . $canonicalPath);
        } else {
            $canonicalHref = city_route('doctor.show', ['handle' => $doctor->handle ?? $doctor->id]);
        }
    @endphp

    <link rel="canonical" href="{{ $canonicalHref }}">

    @if (isset($doctor->seo['noindex']) && !!$doctor->seo['noindex'])
        <meta name="robots" content="noindex">
    @endif

    @if ($doctor->header_scripts)
        {!! $doctor->header_scripts !!}
    @endif

    {!! Clinic::schema()->physician($doctor) !!}
@endpush

<x-app-layout title="{{ $doctor->seo['title'] ?? $doctor->full_name }}" description="{{ $doctor->seo['description'] }}">
    <section class="bg-surface-subdued pb-10 md:pb-16">
        <div class="container py-6 md:py-10">
            <div class="overflow-x-auto no-scrollbar">
                <ol class="flex items-center gap-x-2 gap-y-1 text-xs font-medium text-interactive/50 md:text-sm [&_li]:text-nowrap"
                    itemscope itemtype="https://schema.org/BreadcrumbList">
                    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <a itemprop="item" href="{{ home_route() }}"
                            class="transition hover:text-interactive hover:underline">
                            <span itemprop="name">Главная</span>
                        </a>
                        <meta itemprop="position" content="1" />
                    </li>
                    <li>/</li>
                    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <a itemprop="item" href="{{ city_route('pages.show', ['handle' => $doctorsPage->handle]) }}"
                            class="transition hover:text-interactive hover:underline">
                            <span itemprop="name">{{ $doctorsPage->breadcrumbs_title }}</span>
                        </a>
                        <meta itemprop="position" content="2" />
                    </li>
                    <li>/</li>
                    <li class="text-action-primary" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <span itemprop="name">{{ $doctor->full_name }}</span>
                        <meta itemprop="position" content="3" />
                    </li>
                </ol>
            </div>
            

            <div class="mt-4 rounded-3xl border border-slate-100 bg-white p-3 shadow-sm md:mt-6 md:rounded-3xl md:p-6 lg:p-7">
                <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
                    <div class="shrink-0 lg:w-[300px]">
                        <div
                            class="overflow-hidden rounded-xl bg-slate-100 h-full md:max-h-80 [&_img]:h-full [&_img]:w-full [&_img]:object-contain md:rounded-3xl">
                            @if ($doctor->avatar_image)
                                {{ $doctor->avatar_image }}
                            @else
                                <div class="flex h-full items-center justify-center text-slate-300">
                                    <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"
                                        class="h-24 w-24">
                                        <path
                                            d="M8 7C9.65685 7 11 5.65685 11 4C11 2.34315 9.65685 1 8 1C6.34315 1 5 2.34315 5 4C5 5.65685 6.34315 7 8 7Z"
                                            fill="currentColor" />
                                        <path d="M14 12C14 10.3431 12.6569 9 11 9H5C3.34315 9 2 10.3431 2 12V15H14V12Z"
                                            fill="currentColor" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col gap-6 lg:min-w-0 lg:justify-between">
                        <div class="flex flex-col gap-4 md:gap-5 h-full">
                            <div class="space-y-1 text-interactive/80 md:flex md:flex-wrap md:items-center md:gap-2 md:space-y-0 md:text-sm">
                                <div class="flex items-center gap-1.5">
                                    @for ($i = 0; $i < 5; $i++)
                                        <x-star-alt class="h-5 w-5" />
                                    @endfor
                                </div>
                                <span class="block text-sm leading-5">{{ $ratingText }}</span>
                            </div>

                            <div class="space-y-2 md:space-y-3">
                                <h1 class="text-3xl font-semibold leading-tight text-interactive md:text-4xl">
                                    {{ $doctor->full_name }}
                                </h1>

                                @if (filled($doctor->speciality))
                                    <p class="text-lg font-semibold leading-tight text-interactive md:text-xl">
                                        {{ $doctor->speciality }}
                                    </p>
                                @endif
                            </div>

                            @if (filled(data_get($doctor->extra, 'seniority')) || filled($doctor->receives_display))
                                <div class="grid grid-cols-2 gap-2 md:flex md:flex-wrap md:gap-3">
                                    @if (filled(data_get($doctor->extra, 'seniority')))
                                        <div
                                            class="inline-flex min-h-6 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-center text-xs leading-4 text-interactive/80 md:min-h-8 md:px-6 md:text-sm md:leading-5">
                                            Стаж работы: {{ trim((string) data_get($doctor->extra, 'seniority')) }}
                                        </div>
                                    @endif

                                    @if (filled($doctor->receives_display))
                                        <div
                                            class="inline-flex min-h-6 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-center text-xs leading-4 text-interactive/80 md:min-h-8 md:px-6 md:text-sm md:leading-5">
                                            {{ $doctor->receives_display }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if ($doctor->actual_video_url || $reviewItems->isNotEmpty())
                                <div class="space-y-4 md:hidden">
                                    @if ($doctor->actual_video_url)
                                        <button
                                            class="inline-flex min-h-12 w-full items-center justify-center gap-3 rounded-2xl bg-slate-100 px-5 py-4 text-sm font-medium text-interactive transition hover:text-action-primary"
                                            @click="videoUrl='{{ $doctor->actual_video_url }}'">
                                            <span
                                                class="flex h-8 w-8 items-center justify-center rounded-full bg-action-primary text-white">
                                                <x-icon-play class="h-8 w-8" />
                                            </span>
                                            <span>Видеовизитка врача</span>
                                        </button>
                                    @endif

                                    @if ($reviewItems->isNotEmpty())
                                        <div class="space-y-3">
                                            <h2 class="text-lg font-semibold leading-tight text-interactive">
                                                Отзывы о специалисте:
                                            </h2>

                                            <div class="grid grid-cols-2 gap-2">
                                                @foreach ($reviewItems as $reviewItem)
                                                    <a href="{{ $reviewItem['url'] }}" target="_blank" rel="noopener"
                                                        class="flex min-h-11 items-center overflow-hidden rounded-xl border border-blue-100 bg-white transition hover:border-action-primary/40">
                                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center bg-slate-50">
                                                            {{ $reviewItem['media']->img()->attributes(['class' => 'h-5 w-auto']) }}
                                                        </span>
                                                        <span class="truncate px-3 text-xs font-medium text-interactive">
                                                            {{ $reviewItem['label'] }}
                                                        </span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            
                        </div>

                        <x-button-primary
                            type="button"
                            data-appointment-entry="doctor"
                            data-doctor-id="{{ $doctor->uuid }}"
                            data-doctor-callback-target="otpravka-formy"
                            onclick="ym(94302729,'reachGoal','shapka-forma-open')"
                            class="w-full text-lg md:hidden">
                            Записаться на приём
                        </x-button-primary>
                    </div>

                    <div class="hidden flex-col gap-4 md:flex lg:w-56 lg:items-end lg:justify-between">
                        @if ($doctor->actual_video_url)
                            <button
                                class="inline-flex items-center gap-3 self-start rounded-2xl bg-slate-100 px-5 py-3 text-sm font-medium text-interactive transition hover:text-action-primary lg:self-end"
                                @click="videoUrl='{{ $doctor->actual_video_url }}'">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-action-primary text-white">
                                    <x-icon-play class="h-8 w-8" />
                                </span>
                                <span>Видеовизитка врача</span>
                            </button>
                        @else
                            <div class="inline-flex items-center gap-3 self-start rounded-2xl bg-slate-100 px-5 py-3 text-sm font-medium text-slate-400 lg:self-end">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-300 text-white">
                                    <x-icon-play class="h-8 w-8" />
                                </span>
                                <span>Видеовизитка врача</span>
                            </div>
                        @endif

                        <x-button-primary
                            type="button"
                            data-appointment-entry="doctor"
                            data-doctor-id="{{ $doctor->uuid }}"
                            data-doctor-callback-target="otpravka-formy"
                            onclick="ym(94302729,'reachGoal','shapka-forma-open')"
                            class="w-full lg:max-w-56">
                            Записаться на приём
                        </x-button-primary>
                    </div>
                </div>
            </div>

            @if ($hasInfoSections)
                @if ($hasDesktopInfoSections)
                    <div class="mt-6 hidden rounded-3xl border border-slate-100 bg-white px-5 py-6 md:block lg:p-8">
                        <div class="space-y-8">
                            @if ($reviewItems->isNotEmpty())
                                <section class="border-b border-slate-200/80 pb-7">
                                    <h2 class="text-xl font-semibold leading-tight text-interactive">
                                        Отзывы о специалисте:
                                    </h2>

                                    <div class="flex flex-wrap gap-4 mt-5">
                                        @foreach ($reviewItems as $reviewItem)
                                            <a href="{{ $reviewItem['url'] }}" target="_blank" rel="noopener"
                                                class="flex min-h-10 items-center overflow-hidden rounded-xl border border-blue-100 bg-white transition hover:border-action-primary/40">
                                                <span class="flex h-10 w-12 items-center justify-center bg-slate-50">
                                                    {{ $reviewItem['media']->img()->attributes(['class' => 'h-8 w-auto']) }}
                                                </span>
                                                <span class="px-4 text-xs font-medium text-interactive">
                                                    {{ $reviewItem['label'] }}
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                            @if ($education->isNotEmpty())
                                <section class="border-b border-slate-200/80 pb-7">
                                    <h2 class="text-xl font-semibold leading-tight text-interactive">
                                        Образование:
                                    </h2>

                                    <div class="mt-5 space-y-7">
                                        @foreach ($education as $institution)
                                            <article class="grid gap-y-4 lg:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)] lg:gap-x-10">
                                                <div class="lg:pr-4">
                                                    @if (filled($institution['title']))
                                                        <h3 class="leading-6 text-interactive/70">
                                                            {{ $institution['title'] }}
                                                        </h3>
                                                    @endif
                                                </div>

                                                @if ($institution['items']->isNotEmpty())
                                                    <div class="grid gap-4 sm:grid-cols-2 sm:gap-x-7 sm:gap-y-5">
                                                        @foreach ($institution['items'] as $item)
                                                            <div class="border-l border-slate-200 pl-4">
                                                                @if (filled($item['year']))
                                                                    <p class="leading-5 text-interactive/70">
                                                                        {{ $item['year'] }}
                                                                    </p>
                                                                @endif

                                                                @if (filled($item['specialty']))
                                                                    <p class="mt-1 text-lg font-semibold leading-6 text-interactive">
                                                                        {{ $item['specialty'] }}
                                                                    </p>
                                                                @endif

                                                                @if (filled($item['level']))
                                                                    <p class="mt-1 text-sm leading-6 text-interactive/60">
                                                                        {{ $item['level'] }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            @if ($professionalDevelopment->isNotEmpty())
                                <section class="border-b border-slate-200/80 pb-7">
                                    <h2 class="text-xl font-semibold leading-tight text-interactive">
                                        Повышение квалификации:
                                    </h2>

                                    <div class="mt-5 space-y-4">
                                        @foreach ($professionalDevelopment as $item)
                                            <article class="grid gap-2 md:grid-cols-[3.75rem_minmax(0,1fr)] md:gap-4">
                                                <div class="border-l border-slate-200 pl-4 text-lg font-semibold leading-6 text-interactive">
                                                    {{ $item['year'] }}
                                                </div>
                                                <div class="leading-6 text-interactive/70 md:pt-px">
                                                    {{ $item['title'] }}
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            @if ($skills->isNotEmpty())
                                <section class="border-b border-slate-200/80 pb-7">
                                    <h2 class="text-xl font-semibold leading-tight text-interactive">
                                        Профессиональные навыки:
                                    </h2>

                                    <ol class="mt-5 list-decimal space-y-3 pl-5 text-base leading-8 text-interactive/70 marker:text-interactive">
                                        @foreach ($skills as $skill)
                                            <li>{{ $skill }}</li>
                                        @endforeach
                                    </ol>
                                </section>
                            @endif

                            @if ($documents->isNotEmpty())
                                <section>
                                    <h2 class="text-xl font-semibold leading-tight text-interactive">
                                        Документы, подтверждающие квалификацию
                                    </h2>

                                    <div class="mt-5 flex flex-wrap gap-2.5">
                                        @foreach ($documents as $document)
                                            <a href="{{ $document->getUrl() }}"
                                                class="glightbox group relative block overflow-hidden rounded-xl border border-slate-200/80">
                                                <div class="h-[120px] w-[120px] overflow-hidden bg-slate-100">
                                                    {{ $document->img()->attributes(['class' => 'h-full w-full object-cover transition duration-300 group-hover:scale-105']) }}
                                                </div>
                                                <div
                                                    class="absolute inset-0 hidden items-center justify-center bg-[#30456b]/70 text-sm font-semibold text-white group-hover:flex">
                                                    Просмотр
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($mobileSections->isNotEmpty())
                    <div class="mt-6 rounded-3xl border border-slate-100 bg-white px-5 py-2 md:hidden">
                        <faq inline-template>
                            <div class="divide-y divide-slate-200/80">
                                @foreach ($mobileSections as $section)
                                    <item inline-template>
                                        <details :open="open">
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-5"
                                                @click.prevent="toggle">
                                                <span class="max-w-[15rem] text-xl font-semibold leading-tight text-interactive">
                                                    {{ $section['title'] }}
                                                </span>

                                                <span
                                                    :class="['shrink-0 text-interactive transition duration-200', open ? 'rotate-180' : '']">
                                                    <svg class="h-5 w-5 fill-current" viewBox="0 0 20 20"
                                                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                        <path fill-rule="evenodd"
                                                            d="M6.24 8.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 1 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06Z" />
                                                    </svg>
                                                </span>
                                            </summary>

                                            <div v-show="open" class="pb-5">
                                                @switch($section['key'])
                                                    @case('about')
                                                        <div class="leading-6 text-interactive/70 [&_h2]:mb-3 [&_h2]:text-lg [&_h2]:font-semibold [&_ol]:ml-5 [&_ol]:list-decimal [&_ol]:space-y-2 [&_p]:mb-3 [&_strong]:text-interactive [&_ul]:ml-5 [&_ul]:list-disc [&_ul]:space-y-2">
                                                            {!! $doctor->bio !!}
                                                        </div>
                                                    @break

                                                    @case('education')
                                                        <div class="space-y-5">
                                                            @foreach ($education as $institution)
                                                                <article>
                                                                    @if (filled($institution['title']))
                                                                        <h3 class="text-sm leading-5 text-interactive/60">
                                                                            {{ $institution['title'] }}
                                                                        </h3>
                                                                    @endif

                                                                    @if ($institution['items']->isNotEmpty())
                                                                        <div class="mt-3 space-y-4">
                                                                            @foreach ($institution['items'] as $item)
                                                                                <div>
                                                                                    @if (filled($item['year']))
                                                                                        <p class="text-sm leading-4 text-interactive/60">
                                                                                            {{ $item['year'] }}
                                                                                        </p>
                                                                                    @endif

                                                                                    @if (filled($item['specialty']))
                                                                                        <p class="mt-1 font-semibold leading-5 text-interactive">
                                                                                            {{ $item['specialty'] }}
                                                                                        </p>
                                                                                    @endif

                                                                                    @if (filled($item['level']))
                                                                                        <p class="mt-1 text-sm leading-5 text-interactive/60">
                                                                                            {{ $item['level'] }}
                                                                                        </p>
                                                                                    @endif
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                </article>
                                                            @endforeach
                                                        </div>
                                                    @break

                                                    @case('development')
                                                        <div class="space-y-4">
                                                            @foreach ($professionalDevelopment as $item)
                                                                <article class="grid grid-cols-[2.75rem_minmax(0,1fr)] gap-3">
                                                                    @if (filled($item['year']))
                                                                        <p class="text-sm font-semibold leading-5 text-interactive">
                                                                            {{ $item['year'] }}
                                                                        </p>
                                                                    @endif

                                                                    @if (filled($item['title']))
                                                                        <p class="text-sm leading-5 text-interactive/70">
                                                                            {{ $item['title'] }}
                                                                        </p>
                                                                    @endif
                                                                </article>
                                                            @endforeach
                                                        </div>
                                                    @break

                                                    @case('skills')
                                                        <ol class="list-decimal space-y-2.5 pl-4 text-sm leading-5 text-interactive/70 marker:text-interactive">
                                                            @foreach ($skills as $skill)
                                                                <li>{{ $skill }}</li>
                                                            @endforeach
                                                        </ol>
                                                    @break

                                                    @case('documents')
                                                        <div class="grid grid-cols-3 gap-2">
                                                            @foreach ($documents as $document)
                                                                <a href="{{ $document->getUrl() }}" class="glightbox group overflow-hidden rounded-xl border border-slate-200/80">
                                                                    <div class="aspect-square overflow-hidden bg-slate-100">
                                                                        {{ $document->img()->attributes(['class' => 'h-full w-full object-cover transition duration-300 group-hover:scale-105']) }}
                                                                    </div>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                @endswitch
                                            </div>
                                        </details>
                                    </item>
                                @endforeach
                            </div>
                        </faq>
                    </div>
                @endif
            @endif
        </div>
    </section>

    <video-modal :open="videoUrl !== null" :video-url="videoUrl" @close="videoUrl = null"></video-modal>
</x-app-layout>
