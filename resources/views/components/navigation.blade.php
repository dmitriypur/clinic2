<nav itemscope="" itemtype="http://schema.org/SiteNavigationElement">
    <meta itemprop="name" content="Основное меню">
    <ul class="flex flex-col lg:flex-row lg:items-center lg:space-x-16">
        @foreach ($menu->items as $index => $item)
            <li class="relative group/list" @click="toggleSubNav('{{ $index }}')">
                @if ($item['children'])
                    <span
                        class="peer cursor-pointer py-2 lg:py-4 block lg:flex lg:text-lg font-medium lg:after:absolute after:-bottom-px after:left-0 after:h-[3px] after:w-full {{ $item['active'] ? 'text-action-primary group-hover/list:after:bg-action-primary fill-action-primary' : 'text-interactive fill-interactive lg:group-hover/list:after:bg-interactive' }}">
                        <span class="flex items-center space-x-1">
                            <span>{{ $item['label'] }}</span>
                            <span class="h-5 w-5 block"><x-icon-caret-down /></span>
                        </span>
                    </span>
                    <div :class="[subNavOpen === '{{ $index }}' ? 'block lg:hidden' : 'hidden']"
                        class="lg:absolute lg:top-16 -mt-2 mb-1 lg:-mt-1 lg:-left-10 peer-hover:lg:block group-hover/list:lg:block lg:p-2 pt-2">
                        <ul class="bg-white lg:shadow-lg lg:py-4 px-3 lg:px-6 lg:rounded-lg">
                            @foreach ($item['children'] as $child)
                                <li>
                                    <a href="{{ $child['data']['url'] }}"
                                        class="whitespace-nowrap py-2 block hover:underline font-medium {{ $child['active'] ? 'text-action-primary hover:text-action-primary-hovered' : 'text-interactive hover:text-interactive-hovered' }}">{{ $child['label'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <a href="{{ $item['data']['url'] }}" target="{{ $item['data']['target'] }}"
                        class="group/link relative py-2 block lg:flex lg:py-4 lg:text-lg lg:after:absolute after:bottom-0 after:left-0 after:h-[3px] after:w-full font-medium {{ $item['active'] ? 'text-action-primary hover:after:bg-action-primary' : 'text-interactive lg:hover:after:bg-interactive' }}"
                        itemprop="url">
                        <span
                            class="box-decoration-clone border-b border-b-transparent group-hover/link:border-interactive lg:group-hover/link:border-transparent"
                            itemprop="name">{{ $item['label'] }}</span>
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
