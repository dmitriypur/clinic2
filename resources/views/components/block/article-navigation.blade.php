<div class="article-navigation container">
    <nav
        class="mx-auto flex max-w-[668px] flex-col gap-4 md:grid md:grid-cols-2 md:gap-8"
        aria-label="Навигация по статьям"
    >
        @if($neighbors->previous)
            <a
                href="{{ $neighbors->previous->getUrl() }}"
                rel="prev"
                class="flex min-h-14 items-center justify-center gap-4 rounded-xl border border-interactive px-6 py-3 font-semibold text-interactive hover:bg-interactive hover:text-white"
            >
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Предыдущая статья</span>
            </a>
        @endif

        @if($neighbors->next)
            <a
                href="{{ $neighbors->next->getUrl() }}"
                rel="next"
                class="btn-blue-gradient flex min-h-14 items-center justify-center gap-4 rounded-xl px-6 py-3 font-semibold text-white md:col-start-2"
            >
                <span>Следующая статья</span>
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        @endif
    </nav>
</div>
