<div class="w-full flex flex-col lg:bg-action-primary lg:px-1 lg:pb-2 lg:rounded-2xl">
    <span class="hidden lg:block lg:text-center lg:text-xl lg:font-medium lg:text-white lg:py-3">{{ $itemLabel }}</span>
    <ul class="p-2 grow bg-surface-subdued text-sm rounded-md lg:text-base lg:bg-white lg:py-4 lg:px-8 lg:rounded-xl">
        @foreach ($children as $secondIndex => $second)
            <li class="border-b">
                <span class="relative flex items-center lg:[&_span]:hover:-rotate-90 [&_.test]:hover:block">
                    <div class="test absolute -left-6 top-0 {{ $second['active'] ? 'block' : 'hidden' }}">&#10230;</div>
                    <a href="{{ $second['data']['url'] }}"
                       class="block flex-auto py-1 hover:text-interactive font-medium transition-color duration-300 {{ $second['active'] ? 'text-interactive' : 'text-interactive/50' }}"
                       @mouseenter="setActiveSecond('{{ $menuIndex }}', '{{ $secondIndex }}', '{{ $second['data']['image'] ?? '' }}'); selectDoctor({{ isset($second['data']['doctor']) ? json_encode($second['data']['doctor']) : 'null' }})"
                       @mouseleave="selectDoctor(null)">
                        {{ $second['label'] }}
                    </a>
                    @if (!empty($second['children']))
                        <span class="h-5 w-5 block transition-transform duration-200 ml-auto"
                              @click.prevent="isMobile ? toggleMobileThird('{{ $secondIndex }}') : null">
                            <x-icon-caret-down />
                        </span>
                    @endif
                </span>
                @if(!empty($second['children']))
                    <x-mega-menu.mobile-level-three :secondIndex="$secondIndex" :second="$second"></x-mega-menu.mobile-level-three>
                @endif
            </li>
        @endforeach
    </ul>
</div>
