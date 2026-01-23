<div
    class="rounded-xl bg-white p-4 md:p-6 md:rounded-20 flex md:items-center flex-col md:flex-row gap-4 md:gap-6 md:min-h-36">
    <div class="rounded-xl overflow-hidden relative min-w-[60px] max-w-[60px]">
        {!! $service['image_html'] !!}
    </div>

    <div class="flex flex-col">
        <div class="mb-2 text-xl font-semibold leading-tight">
            <p>{{ $service['title'] }}</p>
        </div>

        <div class="text-interactive leading-tight md:leading-snug">
            {!! $service['body_html'] !!}
        </div>
    </div>
</div>
