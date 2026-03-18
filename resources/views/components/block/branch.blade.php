@php
    $branches = collect($currentCity->branches ?? [])
        ->filter(fn ($branch) => is_array($branch))
        ->values();
@endphp

<div class="container">
    @if(!$block->title_hidden)
        <div class="mx-auto px-10 mb-6 md:mb-12">
            <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    <div class="grid gap-4 md:gap-6 md:grid-cols-2">
        @foreach($branches as $index => $branch)
            <article class="bg-white rounded-20 p-5 md:p-8">
                <div class="space-y-4">
                    <h3 class="font-semibold text-xl md:text-2xl text-heading">
                        {{ $branch['name'] ?? 'Филиал ' . ($index + 1) }}
                    </h3>

                    <div class="space-y-3 text-sm md:text-base">
                        @if(!empty($branch['address']))
                            <div>
                                <div class="text-interactive/60">Адрес</div>
                                <div class="mt-1">{{ $branch['address'] }}</div>
                            </div>
                        @endif

                        @if(!empty($branch['metro']))
                            <div>
                                <div class="text-interactive/60">Метро</div>
                                <div class="mt-1">{{ $branch['metro'] }}</div>
                            </div>
                        @endif

                        @if(!empty($branch['schedule']))
                            <div>
                                <div class="text-interactive/60">Режим работы</div>
                                <div class="mt-1">{{ $branch['schedule'] }}</div>
                            </div>
                        @endif

                        @if(!empty($branch['phone']))
                            <div>
                                <div class="text-interactive/60">Телефон</div>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $branch['phone']) }}"
                                   class="mt-1 inline-block text-action-primary hover:underline">
                                    {{ $branch['phone'] }}
                                </a>
                            </div>
                        @endif

                        @if(!empty($branch['email']))
                            <div>
                                <div class="text-interactive/60">Email</div>
                                <a href="mailto:{{ $branch['email'] }}"
                                   class="mt-1 inline-block text-action-primary hover:underline">
                                    {{ $branch['email'] }}
                                </a>
                            </div>
                        @endif

                        @if(!empty($branch['postal_code']))
                            <div>
                                <div class="text-interactive/60">Индекс</div>
                                <div class="mt-1">{{ $branch['postal_code'] }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</div>
