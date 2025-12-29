<div
    v-show="!isMobile && activeSecond[0] == '{{ $menuIndex }}' && activeSecond[1] == '{{ $secondIndex }}'"
    class="lg:flex lg:flex-col lg:dropdown-in bg-blue-label px-1 pb-2 rounded-2xl">
    <span class="flex items-center gap-2 text-center text-xl font-medium text-white py-3">
        <svg width="15" height="8" viewBox="0 0 15 8" fill="none" xmlns="http://www.w3.org/2000/svg" class="ml-2">
            <path d="M14.3536 4.35355C14.5488 4.15829 14.5488 3.84171 14.3536 3.64645L11.1716 0.464465C10.9763 0.269203 10.6597 0.269203 10.4645 0.464465C10.2692 0.659727 10.2692 0.97631 10.4645 1.17157L13.2929 4L10.4645 6.82843C10.2692 7.02369 10.2692 7.34027 10.4645 7.53553C10.6597 7.7308 10.9763 7.7308 11.1716 7.53553L14.3536 4.35355ZM0 4L4.37114e-08 4.5L14 4.5L14 4L14 3.5L-4.37114e-08 3.5L0 4Z" fill="white"/>
        </svg>
        {{ $secondLabel }}
    </span>
    <ul class="grow bg-white h-full py-4 px-8 rounded-xl">
        @foreach ($children as $third)
            <li class="border-b">
                <a href="{{ $third['data']['url'] }}"
                   class="block py-1 hover:text-blue-label font-medium transition-color duration-300 {{ $third['active'] ? 'text-blue-label' : 'text-interactive/50' }}"
                   @mouseenter="previewImage = '{{ $third['data']['image'] ?? '' }}'">
                    {{ $third['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
