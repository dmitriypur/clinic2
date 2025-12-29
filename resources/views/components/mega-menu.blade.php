<mega-menu :menu-items="{{ json_encode(array_values($menu->items)) }}" inline-template>
    <ul class="flex flex-col lg:flex-row lg:space-x-16 bg-white flex-auto relative">
        @foreach ($menu->items as $menuIndex => $item)
            <x-mega-menu.item :item="$item" :menu-index="$menuIndex" />
        @endforeach
    </ul>
</mega-menu>
