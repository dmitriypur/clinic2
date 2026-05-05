<div class="p-4 lg:pt-16 lg:px-8 shadow lg:shadow-none rounded-lg">
    <div class="flex gap-3 lg:gap-9">
        <div class="space-y-2 max-w-[138px] lg:max-w-60">
            <a href="{{ $doctor->url }}" class="rounded lg:rounded-none block overflow-clip">
                @if($doctor->avatar_image)
                    {{ $doctor->avatar_image }}
                @else
                <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full opacity-30">
                    <path d="M8 7C9.65685 7 11 5.65685 11 4C11 2.34315 9.65685 1 8 1C6.34315 1 5 2.34315 5 4C5 5.65685 6.34315 7 8 7Z" fill="#000000"/>
                    <path d="M14 12C14 10.3431 12.6569 9 11 9H5C3.34315 9 2 10.3431 2 12V15H14V12Z" fill="#000000"/>
                </svg>
                @endif
            </a>
            <div class="flex lg:hidden gap-1 items-center justify-center bg-surface-subdued rounded p-1">
                @for ($i = 0; $i < 5; $i++)
                    <span>
                        <x-icon.star class="w-5 h-5 fill-[#ffcc00]"/>
                    </span>
                @endfor
            </div>
        </div>
        
        <div class="flex flex-col justify-between">
            <div class="flex flex-col justify-between lg:justify-normal space-y-4 h-full lg:h-auto">
                <div class="space-y-1 lg:space-y-3">
                    <div class="lg:pb-2">
                        <p class="text-[13px]/4 lg:text-base pb-2 lg:pb-0">{{ $doctor->speciality }}</p>
                        <a href="{{ $doctor->url }}"
                           class="text-lg/6 lg:text-2xl font-semibold text-left text-interactive hover:underline w-min block lg:w-auto">{{ $doctor->full_name }}</a>
                    </div>

                    @if ($doctor->extra)
                        <div class="pt-1 lg:pt-0">
                            @if(!empty($doctor->extra['seniority']))
                            <div class="flex gap-1 text-[13px] lg:text-base">
                                <div class="text-interactive font-semibold">
                                    Стаж работы:
                                </div>
                                <div>{{ $doctor->extra['seniority'] }}</div>
                            </div>
                            @endif
                            @if(!empty($doctor->extra['category']))
                            <div class="flex gap-1 text-[13px] lg:text-base">
                                <div class="text-interactive font-semibold">
                                    Категория:
                                </div>
                                <div>{{ $doctor->extra['category'] }}</div>
                            </div>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($doctor->actual_video_url)
                    <div class="pb-0.5">
                        <button class="flex gap-2 items-center group cursor-pointer" type="button"
                                data-doctor-video-url="{{ $doctor->actual_video_url }}">
                            <span class="text-action-primary">
                                <x-icon-play class="w-6 h-6 fill-current"/>
                            </span>
                            <span
                                class="font-medium text-interactive border-b border-b-transparent group-hover:border-b-interactive text-sm/none lg:text-base/none">видеовизитка</span>
                        </button>
                    </div>
                @endif

                <div class="hidden invisible lg:visible lg:flex gap-1.5 items-center">
                    @for ($i = 0; $i < 5; $i++)
                        <span>
                            <x-icon.star class="w-6 h-6 fill-[#ffcc00]"/>
                        </span>
                    @endfor
                    <span
                        class="hidden lg:block pt-1 pl-2">{{ $doctor->extra['rating'] ?? '100% пациентов рекомендуют врача' }}</span>
                </div>
            </div>
            <div class="hidden lg:block">
                <x-button-primary
                    onclick="ym(94302729,'reachGoal','shapka-forma-open')"
                    type="button"
                    data-appointment-entry="doctor"
                    data-doctor-id="{{ $doctor->uuid }}"
                    data-skip-birth-date="true"
                    data-doctor-callback-target="otpravka-formy">
                    Записаться на прием
                </x-button-primary>
            </div>
        </div>
    </div>
    <div class="lg:hidden mt-4">
        <x-button-primary
            onclick="ym(94302729,'reachGoal','shapka-forma-open')"
            type="button"
            data-appointment-entry="doctor"
            data-doctor-id="{{ $doctor->uuid }}"
            data-skip-birth-date="true"
            data-doctor-callback-target="otpravka-formy"
            class="w-full">
            Записаться на прием
        </x-button-primary>
    </div>
</div>
