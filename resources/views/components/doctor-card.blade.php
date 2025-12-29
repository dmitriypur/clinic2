<div class="flex flex-col rounded-xl p-4 lg:p-4 bg-white h-full">
    <div>
        <div class="header__top flex justify-between items-center">
            <div class="stars flex [&_svg]:w-5 [&_svg]:h-5">
                <x-star-alt class="mr-0.5"></x-star-alt>
                <x-star-alt class="mr-0.5"></x-star-alt>
                <x-star-alt class="mr-0.5"></x-star-alt>
                <x-star-alt class="mr-0.5"></x-star-alt>
                <x-star-alt></x-star-alt>
            </div>
            <p class="recommend text-es opacity-50">100% пациентов рекомендуют врача</p>
        </div>
        <p class="doctor__card-header--name text-xl leading-6 font-bold mt-2">{{ $doctor->surname }}
            <br>{{ $doctor->name }}</p>
    </div>
    <div class="flex flex-col mt-4 relative z-0">
        <ul class="flex gap-2.5 flex-col">
            <li class="bg-surface-subdued py-2 px-3 rounded-md">
                <p class="text-xs leading-[100%] opacity-60">Специальность:</p>
                <p class="text-sm leading-4 mt-0.5">{{ explode(',', $doctor->speciality)[0] }}</p>
            </li>
            <li class="bg-surface-subdued py-2 px-3 rounded-md">
                <p class="text-xs leading-[100%] opacity-60">Врачебный стаж:</p>
                <p class="text-sm font-semibold leading-4 mt-0.5">{{ $doctor->extra['seniority'] }}</p>
            </li>
            <li class="bg-surface-subdued py-2 px-3 rounded-md">
                <p class="text-xs leading-[100%] opacity-60">Ведёт приём:</p>
                <p class="text-xs font-semibold leading-4 mt-0.5">{{ $doctor->extra['receives'] }}</p>
            </li>
        </ul>
        <div class="flex items-end gap-2 relative mt-2.5">
            <a href="{{city_route('doctor.show', [$doctor->id])}}"
               class="p-2 bg-surface-subdued rounded-md flex flex-col items-center shrink-0 gap-2 text-xs">
                Карточка врача
                <x-icon-card></x-icon-card>
            </a>
            <div class="absolute max-w-44 bottom-4 right-0">
                {{ $doctor->avatar_image }}
            </div>

            @if ($doctor->actual_video_url)
                <button
                    @click="videoUrl='{{ $doctor->actual_video_url }}'"
                    class="flex justify-center items-center w-full h-8 rounded-lg text-sm z-10 orange-gr">
                                            <span
                                                class="flex justify-center items-center w-6 h-6 rounded-full bg-white mr-2">
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="14"
                                                    height="14"
                                                    viewBox="0 0 24 24"
                                                    fill="#F5841F"
                                                    stroke="none"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="lucide lucide-play ml-0.5"
                                                >
                                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                              </svg>
                                            </span>
                    <span class="text-sm text-medium text-white">видеовизитка</span>
                </button>
            @else
                <div
                    class="bg-surface-subdued flex justify-center gap-1.5 items-center z-10 w-full h-8 rounded-lg text-sm">
                                            <span class="text-icon-subdued">
                                                <x-icon-play
                                                    class="w-6 fill-current"/>
                                            </span>
                    <span
                        class="text-sm">видеовизитка</span>
                </div>
            @endif
        </div>

    </div>
    <div class="mt-auto relative z-10">
        <x-button-primary
            @click="showCallbackModal(null, 'otpravka-formy')"
            class="w-full font-bold rounded-lg mt-3 md:px-0">
            Записаться на приём
        </x-button-primary>
    </div>
</div>
