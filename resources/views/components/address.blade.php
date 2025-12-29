<div class="relative w-full max-w-48 accessibility:max-w-96">
    <div class="relative w-full h-14 flex items-start justify-end gap-1 py-1.5 px-2.5 border border-interactive rounded-lg bg-surface-subdued">
        <img src="{{ asset('images/metro.webp') }}" alt="Metro icon" width="17" height="20">
        <span class="text-es">{{ $metro }}</span>
    </div>
    <div class="absolute top-8 font-medium bg-white w-full py-2 px-2.5 border border-interactive rounded-lg accessibility:grid accessibility:grid-cols-2">
        <div class="flex gap-1">
            <span class="inline-flex w-[13px] h-4 text-icon-interactive">
               <x-icon-map-pin></x-icon-map-pin>
            </span>
            <a href="{{ city_route('pages.show', ['handle' => 'kontakty']) }}" class="text-xs tracking-tighter">{{ $address }}</a>
        </div>
        <div class="text-sm mt-2">{!! str_replace(';', '', trim($schedule)) !!}</div>
        @if($showSpecialSchedule ?? false)
            <a href="/storage/{{ $specialSchedule }}"
               class="text-lg pt-1 block font-medium text-interactive"
               target="_blank"><span>{{ $specialScheduleTitle }}</span></a>
        @endif
    </div>
</div>
