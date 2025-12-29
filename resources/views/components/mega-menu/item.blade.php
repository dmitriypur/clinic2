<li class="group {{ $item['is_simple'] ? 'relative' : '' }}"
    @mouseenter="setActiveTop('{{ $menuIndex }}', '{{ findActivePath($item['children'])['parent'] ?? 0 }}', '{{ findActivePath($item['children'])['image'] ?? ($item['data']['image'] ?? '') }}')"
    @mouseleave="clearActive()">
    <span class="flex items-center lg:[&_span]:hover:rotate-180">
        <a href="{{ $item['data']['url'] }}"
           class="dropdown-mega group/link relative py-2 lg:py-4 lg:text-lg lg:after:absolute after:bottom-0 after:left-0 after:h-[3px] after:w-0 hover:after:w-full after:transition-all after:duration-300 font-medium hover:text-action-primary {{ $item['active'] ? 'text-action-primary hover:after:bg-action-primary' : 'text-interactive lg:hover:after:bg-interactive' }}">
            {{ $item['label'] }}
        </a>
        @if (!empty($item['children']))
            <span class="h-5 w-5 block transition-transform duration-200 ml-auto"
                  @click.prevent="toggleMobileSub('{{ $menuIndex }}')">
                <x-icon-caret-down />
            </span>
        @endif
    </span>

    @if (!empty($item['children']))
        <x-mega-menu.dropdown :item="$item" :menu-index="$menuIndex" />
    @endif
</li>
{{--, '{{ findActivePath($item['children'])[0] ?? 0 }}', '{{ findActivePath($item['children'])[1] ?? ($item['data']['image'] ?? '') }}'--}}
