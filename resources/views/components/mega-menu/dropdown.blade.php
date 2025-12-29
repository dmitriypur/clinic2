<div class="transition-all duration-300 z-50"
     :class="{
        'dropdown-in': isMobile ? mobileSubIndex === '{{ $menuIndex }}' : activeTop === '{{ $menuIndex }}',
        'hidden': isMobile ? mobileSubIndex !== '{{ $menuIndex }}' : activeTop !== '{{ $menuIndex }}',
        'lg:absolute left-0 top-10 lg:pt-6 w-full': !{{ $item['is_simple'] ? 'true' : 'false' }},
        'lg:absolute top-full left-0 w-72 pt-1': {{ $item['is_simple'] ? 'true' : 'false' }}
     }">
    @if($item['is_doctor_grid'])
        {{-- МАКЕТ ДЛЯ РАЗДЕЛА "ВРАЧИ" (2 колонки) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Колонка 1 и 2: Список врачей --}}
            <x-mega-menu.level-two :children="$item['children']" :menu-index="$menuIndex" :item-label="$item['label']"/>

            {{-- Колонка 3: Карточка врача (теперь полностью управляется Vue) --}}
            <div class="hidden lg:block accessibility:hidden">
                <doctor-card v-if="selectedDoctor" :doctor="selectedDoctor" :key="selectedDoctor.id"></doctor-card>
            </div>
        </div>
    @elseif($item['is_simple'])
        {{-- ПРОСТОЙ МАКЕТ (СПИСОК) --}}
        <div class="bg-white rounded-lg shadow-xl py-2 px-0 border border-gray-100 overflow-hidden">
            <ul class="flex flex-col">
                @foreach ($item['children'] as $child)
                    <li>
                        <a href="{{ $child['data']['url'] }}"
                           class="block px-4 py-2 text-sm transition-colors duration-200 hover:bg-surface-subdued hover:text-action-primary {{ $child['active'] ? 'text-action-primary font-medium bg-surface-subdued' : 'text-gray-700' }}">
                            {{ $child['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        {{-- СТАНДАРТНЫЙ МАКЕТ (3 колонки с картинкой) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Уровень 2 --}}
            <x-mega-menu.level-two :children="$item['children']" :menu-index="$menuIndex" :item-label="$item['label']"/>

            {{-- Уровень 3 --}}
            @foreach ($item['children'] as $secondIndex => $second)
                @if(!empty($second['children']))
                    <x-mega-menu.level-three :children="$second['children']" :menu-index="$menuIndex" :second-index="$secondIndex" :second-label="$second['label']"/>
                @endif
            @endforeach

            {{-- Картинка --}}
            <x-mega-menu.image v-if="previewImage"/>
        </div>
    @endif
</div>
