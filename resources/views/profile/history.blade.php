<x-profile-layout>
    <h1 class="font-semibold text-2xl md:text-4xl text-center md:text-left text-heading">Записи на приём</h1>

    <div class="space-y-2 mt-6 mb-10">
        @foreach($user->patients as $patient)
            @if (!count($patient['appointments']))
                @continue
            @endif

            @foreach($patient['appointments'] as $appointment)
                <div class="flex flex-col lg:flex-row px-4 py-4 border rounded-lg lg:items-center gap-1 lg:gap-4">
                    <span class="whitespace-nowrap w-1/3 font-medium lg:font-normal">{{ $patient['name'] }}</span>
                    <span
                        class="w-1/3 whitespace-nowrap font-medium text-action-primary">{{ $appointment['datetime'] }}</span>
                    {{--                    <span>{{ $appointment['adress'] }}/{{ $appointment['theme'] }}</span>--}}
                    <span>{{ $appointment['theme'] }}</span>
                </div>
            @endforeach
        @endforeach
    </div>

    <h1 class="font-semibold text-2xl md:text-4xl text-center md:text-left text-heading">История приёма</h1>

    <div class="mt-6 border rounded-xl overflow-hidden divide-y">
        <div class="hidden lg:grid grid-cols-12 gap-8 p-4 bg-[#ECF1F4] font-medium">
            <span class="col-span-4">ФИО</span>
            <span class="col-span-3">Дата и время приёма</span>
            <span class="col-span-5">Специалист</span>
        </div>
        @foreach($user->patients as $patient)
            @if (!count($patient['visits']))
                @continue
            @endif

            @foreach($patient['visits'] as $index => $visit)
                <div
                    class="relative -top-px flex flex-col lg:grid grid-cols-12 lg:gap-8 p-4 {{ $index % 2 == 0 ?  'bg-surface' : 'bg-surface lg:bg-[#ECF1F4]' }} ">
                    <span class="col-span-4 font-medium lg:font-normal">{{ $patient['name'] }}</span>
                    <span class="col-span-3">{{ $visit['date'] }}</span>
                    <span class="col-span-5">{{ $visit['doctor'] }}</span>
                </div>
            @endforeach
        @endforeach
    </div>
</x-profile-layout>
