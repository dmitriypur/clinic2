<div class="container">
    <div>
        @if(!$block->title_hidden)
            <div class="mx-auto md:rounded-2xl bg-action-primary-light p-4 md:p-6">
                <h2 class="font-semibold text-2xl md:text-3xl text-center text-heading">
                    {{ $block->title }}
                </h2>
            </div>
        @endif

        <div>
            @if(!empty($block->payload['contacts']))
                <div class="grid md:grid-cols-2 gap-8 md:gap-16">
                    @foreach($block->payload['contacts'] as $contact)
                        <div class="bg-white rounded-20">
                            <!-- Заголовок организации -->
                            <div class="flex items-center justify-center bg-interactive text-white p-4 rounded-20 md:h-[98px]">
                                <h3 class="font-medium text-xl/6 md:text-2xl/6 text-center">
                                    {{ $contact['title'] ?? '' }}
                                </h3>
                            </div>

                            <!-- Контактная информация -->
                            <div class="px-6 pt-5 pb-8 md:px-12 md:py-10">
                                @if(!empty($contact['info']))
                                    <div class="flex items-start space-x-2">
                                        <div class="[&_ul]:space-y-2 [&_li]:relative [&_li]:pl-3 [&_li]:before:absolute [&_li]:before:rounded-full [&_li]:before:bg-interactive [&_li]:before:w-1 [&_li]:before:h-1 [&_li]:before:left-0 [&_li]:before:top-2.5">
                                            {!! $contact['info'] !!}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                        <div>
                            <div class="relative">
                                <img src="{{ '/storage/' . $block->payload['image'] }}" alt="">
                            </div>
                        </div>
                </div>
            @endif
        </div>
    </div>
</div>
