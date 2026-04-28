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

    @if(!empty($branches))
        <div class="flex flex-wrap justify-center gap-6">
            @foreach($branches as $index => $branch)
                <article class="bg-white rounded-20 p-5 md:p-8 w-full max-w-[400px]">
                    <div class="space-y-4">
                        <h3 class="font-semibold text-xl md:text-2xl text-heading">
                            {{ !empty($branch['address']) ? $branch['address'] : $branch['name'] }}
                        </h3>

                        <div class="space-y-3 text-sm md:text-base">

                            @if(!empty($branch['metro']))
                                <div class="flex items-center gap-2">
                                    <img src="{{ asset('images/metro2.webp') }}" alt="Иконка метро" width="16" height="16">
                                    <p>{{ $branch['metro'] }}</p>
                                </div>
                            @endif

                            <hr class="bg-transparent border border-dashed">

                            @if(!empty($branch['schedule']))
                                <div>
                                    <div class="font-semibold">Режим работы</div>
                                    <div class="mt-1">{{ $branch['schedule'] }}</div>
                                </div>
                                <hr class="bg-transparent border border-dashed">
                            @endif
                            

                            @if(!empty($branch['phone']))
                                <div>
                                    <div class="font-semibold">Телефон</div>
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $branch['phone']) }}"
                                    class="mt-1 inline-block text-action-primary hover:underline">
                                        {{ $branch['phone'] }}
                                    </a>
                                </div>
                                <hr class="bg-transparent border border-dashed">
                            @endif
                            

                            @if(!empty($branch['email']))
                                <div>
                                    <div class="font-semibold">Email</div>
                                    <a href="mailto:{{ $branch['email'] }}"
                                    class="mt-1 inline-block text-action-primary hover:underline">
                                        {{ $branch['email'] }}
                                    </a>
                                </div>
                            @endif

                            <x-button-dark-border
                                @click="showCallbackModal(null, 'otpravka-formy')"
                                class="w-full mt-4">
                                Записаться на приём
                            </x-button-dark-border>
                            
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
