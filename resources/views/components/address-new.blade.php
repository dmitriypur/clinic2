<div class="relative w-full">
    <div class="top-8 font-medium bg-white w-full flex items-center gap-4">
        @php
            $branches = $currentCity->branches ?? [];
        @endphp

        <div class="flex gap-1 items-center relative" data-branches-dropdown="1">
       
                @if($cities->count() < 2)
                    <button
                        type="button"
                        class="flex items-center gap-1 @if(empty($branches)) cursor-auto @endif"
                        @click="branchesOpen = !branchesOpen"
                    >
                        <p class="tracking-tighter">{{ $address }}</p>
                        @if(!empty($branches))
                            <span class="flex w-8 h-8 shadow rounded ml-2">
                                <x-icon-caret-down />
                            </span>
                        @endif
                    </button>
                    @if(!empty($branches))
                        <div
                            class="absolute left-0 top-8 w-full bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 z-50 overflow-hidden"
                            v-show="branchesOpen"
                        >
                            <ul class="py-2">
                                @foreach($branches as $branch)
                                    <li class="px-3 py-1.5 text-sm leading-snug border-b last:border-b-0">
                                        @if(!empty($branch['address']))
                                            <div>{{ $branch['address'] }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @else
                    <p class="hidden tracking-tighter">{{ $address }}</p>
                @endif
       
        </div>
        <div class="[&_span]:font-semibold [&_span]:text-action-primary [&_span]:ml-2">{!! str_replace(';', '', trim($schedule)) !!}</div>
        @if($showSpecialSchedule ?? false)
            <a href="/storage/{{ $specialSchedule }}"
               class="text-lg pt-1 block font-medium text-interactive"
               target="_blank"><span>{{ $specialScheduleTitle }}</span>
            </a>
        @endif
        
    </div>
</div>
