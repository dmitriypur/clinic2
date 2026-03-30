@php
    $sections = collect($block->apparatus_treatment_sections ?? []);
@endphp

@if($sections->isNotEmpty())
    <div class="container">
        <div class="rounded-3xl bg-white p-4 md:px-10 md:py-10">
            <div class="flex flex-col gap-8 md:gap-10">
                @foreach($sections as $section)
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between md:gap-10">
                        <div class="md:w-1/2 md:pr-4">
                            <h2 class="text-2xl md:text-4xl leading-tight font-semibold text-heading">
                                {{ $section['title'] }}
                            </h2>

                            @if(!empty($section['body_html']))
                                <div class="mt-2.5 text-base leading-[1.4] text-heading [&_p:not(:last-child)]:mb-4">
                                    {!! str($section['body_html'])->sanitizeHtml() !!}
                                </div>
                            @endif
                        </div>

                        @if(!empty($section['image_html']))
                            <div class="h-48 overflow-hidden rounded-3xl md:h-72 md:w-1/2 md:rounded-20 [&_img]:size-full [&_img]:object-cover">
                                {!! $section['image_html'] !!}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
