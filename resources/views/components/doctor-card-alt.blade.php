<div class="flex flex-col rounded-xl p-4 lg:p-4 bg-white h-full accessibility:border accessibility:!font-bold">
    <div>
        <div class="header__top flex justify-between items-start">
            <div class="max-w-[116px]">
                <div class="stars flex [&_svg]:w-5 [&_svg]:h-5 accessibility:hidden">
                    <x-star-alt class="mr-0.5"></x-star-alt>
                    <x-star-alt class="mr-0.5"></x-star-alt>
                    <x-star-alt class="mr-0.5"></x-star-alt>
                    <x-star-alt class="mr-0.5"></x-star-alt>
                    <x-star-alt></x-star-alt>
                </div>
                <p class="text-xs leading-tight text-interactive/60 mt-1.5">100% пациентов рекомендуют врача</p>
            </div>
            <a href="{{ city_route('doctor.show', [$doctor->id]) }}" class="flex items-center gap-x-2.5 py-1.5 px-3 border border-interactive rounded-xl [&_svg]:accessibility:hidden accessibility:border-none accessibility:p-0">
                <x-icon.doctor-card></x-icon.doctor-card>
                <p class="text-xs"><b>Карточка</b> врача</p>
            </a>
        </div>
        <p class="doctor__card-header--name text-2xl leading-6 mt-4">
            <span class="font-bold">{{ $doctor->surname }}</span>
            <br>{{ $doctor->name }}</p>
    </div>
    <div class="flex flex-col mt-4 relative z-0 h-full">
        <ul class="flex gap-2.5 flex-col relative flex-auto">
            <li class="bg-transparent backdrop-blur-sm border border-l-[10px] border-l-blue-label py-3 px-2 rounded-md w-9/12 relative overflow-hidden before:absolute  before:white-to-gray-gradient before:inset-0 before:accessibility:content-[none] before:opacity-30 before:-z-10">
                <p class="text-xs font-bold">Специальность:</p>
                <p class="text-sm leading-4">{{ explode(',', $doctor->speciality)[0] }}</p>
            </li>
            <li class="bg-transparent backdrop-blur-sm border border-l-[10px] border-l-action-primary py-3 px-2 rounded-md w-7/12 relative overflow-hidden before:absolute  before:white-to-blue-gradient before:inset-0 before:accessibility:content-[none] before:opacity-30 z-50 before:-z-10">
                <p class="text-xs font-bold">Ведёт приём:</p>
                <p class="text-sm leading-4">{{ $doctor->receives_display }}</p>
            </li>
            <li class="bg-transparent backdrop-blur-sm border border-l-[10px] border-l-blue-label py-3 px-2 rounded-md w-6/12 md:w-5/12 relative overflow-hidden before:absolute  before:white-to-orange-gradient before:inset-0 before:accessibility:content-[none] before:opacity-30 z-50 before:-z-10">
                <p class="text-xs font-bold">Врачебный стаж:</p>
                <p class="text-sm leading-4">{{ data_get($doctor->extra, 'seniority') }}</p>
            </li> 
        </ul>
        <div class="flex w-full gap-2 relative mt-2.5">
            <div class="absolute max-w-48 bottom-0 -right-4 md:right-0 accessibility:hidden">
                {{ $doctor->avatar_image }}
            </div>
        </div>
    </div>
    <div class="mt-auto relative z-10 flex justify-between gap-2">
        <x-button-primary
            type="button"
            data-appointment-entry="doctor"
            data-doctor-id="{{ $doctor->uuid }}"
            data-doctor-callback-target="otpravka-formy"
            class="w-1/2 font-bold py-2 md:px-0">
            Записаться
        </x-button-primary>

        @if ($doctor->actual_video_url)
            <button
                @click="videoUrl='{{ $doctor->actual_video_url }}'"
                class="accessibility:relative accessibility:bottom-0 flex justify-center items-center w-1/2 h-full rounded-xl btn-blue-gradient">
                <span
                    class="flex justify-center items-center w-5 h-5 rounded-full bg-white mr-2 accessibility:hidden">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="#3981F1"
                        stroke="none"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-play ml-0.5"
                    >
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                </span>
                <span class="font-semibold leading-3 text-white">видео-визитка</span>
            </button>
        @else
            <div
                class="accessibility:relative accessibility:bottom-0 accessibility:!bg-black accessibility:!rounded-none bg-surface-subdued flex justify-center gap-1.5 items-center w-1/2 h-full rounded-xl">
                                        <span class="text-icon-subdued accessibility:hidden">
                                            <x-icon-play
                                                class="w-5 fill-current"/>
                                        </span>
                <span
                    class="font-semibold">видео-визитка</span>
            </div>
        @endif
    </div>
</div>
