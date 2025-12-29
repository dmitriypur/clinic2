<div
    class="lg:w-1/3 lg:bg-action-primary lg:px-1 lg:pb-2 lg:pt-8 lg:ml-4 lg:rounded-2xl"
    :class="{
            'dropdown-in': isMobile ? mobileThirdIndex === '{{ $secondIndex }}' : activeTop === '{{ $secondIndex }}',
            'hidden': isMobile ? mobileThirdIndex !== '{{ $secondIndex }}' : activeTop !== '{{ $secondIndex }}',
        }"
>
    <ul
        class="rounded-md bg-white h-full px-2 lg:p-4 lg:rounded-xl"
    >
        @foreach ($second['children'] ?? [] as $third)
        <li>
            <a href="{{ $third['data']['url'] }}"
               class="block py-1 hover:text-action-primary"
               @mouseenter="previewImage = '{{ $third['data']['image'] ?? '' }}'"
            >
                {{ $third['label'] }}
            </a>
        </li>
        @endforeach
    </ul>
</div>
