<mega-menu :menu-items="{{ json_encode(array_values($menu->items)) }}" inline-template>
    <ul class="flex flex-col lg:flex-row lg:space-x-8 bg-white flex-auto divide-y lg:divide-none">
        @foreach ($menu->items as $menuIndex => $item)
            <x-mega-menu.item :item="$item" :menu-index="$menuIndex" />
        @endforeach
    </ul>
</mega-menu>