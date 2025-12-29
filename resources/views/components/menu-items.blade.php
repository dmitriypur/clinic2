<div :class="[subNavOpen === '{{ $index }}' ? 'block lg:hidden' : 'hidden']"
     class="lg:absolute lg:top-16 -mt-2 mb-1 lg:-mt-1 lg:-left-10 peer-hover:lg:block group-hover/list:lg:block lg:p-2 pt-2">
    <ul class="bg-white lg:shadow-lg lg:py-4 px-3 lg:px-6 lg:rounded-lg">
        @foreach ($children as $childIndex => $child)
            <li>
                @if ($child['children'])
                    <a href="{{ $child['data']['url'] }}"
                       class="whitespace-nowrap py-2 block hover:underline font-medium">{{ $child['label'] }}</a>

                    <x-menu-items :index="$childIndex" :children="$child['children']"></x-menu-items>
                @else
                    <a href="{{ $child['data']['url'] }}"
                       class="whitespace-nowrap py-2 block hover:underline font-medium">{{ $child['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ul>
</div>
