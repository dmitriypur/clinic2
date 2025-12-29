
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
            <a href="{{ $doctor['url'] }}" class="flex items-center gap-x-2.5 py-1 px-3 border-[0.5px] border-interactive rounded-md">
                <x-icon.doctor-card></x-icon.doctor-card>
                <p class="text-es"><b>Карточка</b> врача</p>
            </a>
        </div>
        <p class="doctor__card-header--name text-2xl leading-6 mt-2">
            <span class="font-bold">{{ $doctor['surname'] }}</span>
            <br>{{ $doctor['name'] }}</p>
    </div>
    <div class="flex flex-col mt-4 relative z-0">
        <ul class="flex gap-2.5 flex-col">
            <li class="bg-blue-superlite py-3 pr-3 pl-6 rounded-md relative before:absolute before:h-full before:w-3 before:blue-gradient-nohover before:top-0 before:left-0 before:rounded">
                <p class="text-es font-bold">Специальность:</p>
                <p class="text-xs leading-4">{{ explode(',', $doctor['speciality'])[0] }}</p>
            </li>
            <li class="flex gap-4">
                <span class="text-[8px] max-w-20 text-interactive/60 self-center">100% пациентов рекомендуют врача</span>
                <span class="py-3 pr-3 pl-6 w-full rounded-md bg-blue-superlite relative before:absolute before:h-full before:w-3 before:orange-gr-nohover before:top-0 before:left-0 before:rounded">
                    <p class="text-es font-bold">Врачебный стаж:</p>
                    <p class="text-xs leading-4">{{ $doctor['extra']['seniority'] }}</p>
                </span>
            </li>
            <li class="bg-blue-superlite py-3 pr-3 pl-6 rounded-md relative before:absolute before:h-full before:w-3 before:superlight-blue-gradient before:top-0 before:left-0 before:rounded">
                <p class="text-es font-bold">Ведёт приём:</p>
                <p class="text-xs leading-4">{{ $doctor['extra']['receives'] }}</p>
            </li>
        </ul>
        <div class="flex w-full gap-2 relative mt-2.5">
            <div class="absolute max-w-48 bottom-5 right-0">
                <img src="{{ $doctor['avatar'] }}" alt="Доктор {{ $doctor['surname'] }} {{ $doctor['name'] }}">
            </div>

            @if (!empty($doctor['video_url']))
                <button
                    @click="videoUrl='{{ $doctor['video_url'] }}'"
                    class="absolute -bottom-4 right-0 flex justify-center items-center w-40 h-10 rounded-lg text-sm z-10 blue-gradient-nohover">
                    <span
                        class="flex justify-center items-center w-5 h-5 rounded-full bg-white mr-2">
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
                    <span class="text-es font-semibold text-white">видео-визитка</span>
                </button>
            @else
                <div
                    class="absolute -bottom-4 right-0 bg-surface-subdued flex justify-center gap-1.5 items-center z-10 w-40 h-10 rounded-lg text-sm">
                                            <span class="text-icon-subdued">
                                                <x-icon-play
                                                    class="w-5 fill-current"/>
                                            </span>
                    <span
                        class="text-es font-semibold">видео-визитка</span>
                </div>
            @endif
        </div>

    </div>
    <div class="mt-auto relative z-10">
        <x-button-primary
            @click="showCallbackModal(null, 'otpravka-formy')"
            class="w-full font-bold mt-7 py-3.5 text-lg md:px-0">
            Записаться на приём
        </x-button-primary>
    </div>
</div>
