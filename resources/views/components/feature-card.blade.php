<div
    class="rounded-xl bg-white py-4 px-4 md:px-6 md:rounded-20 flex items-center gap-4 md:gap-8 md:min-h-36">
    <div class="rounded-xl overflow-hidden relative min-w-16 max-w-16 md:min-w-24 md:max-w-24">
        {!! $service['image_html'] !!}
    </div>

    <div class="flex flex-col">
        <div class="mb-2 font-semibold leading-tight">
            <p>{{ $service['title'] }}</p>
        </div>

        <div class="text-sm md:text-base text-interactive/60 leading-tight md:leading-snug">
            {!! $service['body_html'] !!}
        </div>
    </div>
</div>
