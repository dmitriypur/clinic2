<div class="container content">
    @php
        $author = $block->author;
        $hasAuthor = isset($block->payload['author']) && $author;
        $readingTime = $block->page->reading_time_minutes;
    @endphp

    <div class="flex flex-col md:flex-row items-stretch md:justify-between relative gap-6 bg-white rounded-lg md:rounded-20 p-4 md:py-6 md:px-10 ">
        <!-- Верхняя часть -->
        @if($hasAuthor)
            <div
                class="flex flex-col items-center md:flex-row space-y-4 sm:space-y-0 sm:space-x-4 w-full md:w-1/2">
                <div
                    class="h-16 w-16 lg:h-28 lg:w-28 overflow-hidden rounded-full flex bg-gray-100 border [&_img]:object-contain">
                    {!! $author->avatar_image !!}
                </div>
                <div class="text-center sm:text-left">
                    <p class="text-interactive text-sm">Автор статьи:</p>
                    @if(!empty($block->payload['url']))
                        <a href="{{ city_route('pages.show', ['handle' => $block->payload['url']]) }}"
                           class="text-lg md:text-2xl font-semibold text-action-primary underline hover:text-action-primary-hovered">
                            {{ $author->full_name }}
                        </a>
                    @else
                        <span class="text-lg md:text-2xl font-semibold text-interactive">
                            {{ $author->full_name }}
                        </span>
                    @endif
                    <p class="text-gray-700 mt-1">
                        {{ $author->speciality }}
                    </p>
                </div>
            </div>

        @endif

        <div class="flex flex-col justify-center gap-3">
            <p class="hidden md:block text-interactive text-sm">Поделиться:</p>
            <div class="accessibility:hidden flex items-center justify-center gap-3">
                @if ($socials['youtube'] ?? false)
                    <a href="{{ $socials['youtube'] }}" target="_blank" rel="nofollow"
                    class="flex items-center bg-gradient-to-b from-[#FF7E7A] to-[#B3221D] w-12 h-12 rounded-full pl-1">
                        <span class="text-white [&_svg]:h-10 [&_svg]:w-10">
                            <x-icon-youtube/>
                        </span>
                    </a>
                @endif

                @if ($socials['telegram'] ?? false)
                    <a href="{{ $socials['telegram'] }}" target="_blank" rel="nofollow"
                    class="flex items-center bg-gradient-to-b from-[#9ADDFF] to-[#00A8FC] w-12 h-12 rounded-full pl-2">
                        <span class="text-white [&_svg]:h-7 [&_svg]:w-7">
                            <x-icon-telegram/>
                        </span>
                    </a>
                @endif

                @if ($socials['vk'] ?? false)
                    <a href="{{ $socials['vk'] }}" target="_blank" rel="nofollow"
                    class="flex items-center bg-gradient-to-b from-[#B3D2FF] to-[#3D80E0] w-12 h-12 rounded-full pl-2">
                        <span class="text-white [&_svg]:h-7 [&_svg]:w-7">
                            <x-icon-vk/>
                        </span>
                    </a>
                @endif

                @if ($socials['rutube'] ?? false)
                    <a href="{{ $socials['rutube'] }}" target="_blank" rel="nofollow"
                    class="flex items-center w-12 h-12 rounded-full">
                        <span class="text-white [&_svg]:h-12 [&_svg]:w-12">
                            <x-icon-rutube/>
                        </span>
                    </a>
                @endif
                @if ($socials['vk_video'] ?? false)
                    <a href="{{ $socials['vk_video'] }}" target="_blank" rel="nofollow"
                    class="flex items-center w-12 h-12 rounded-full">
                        <span class="text-white [&_svg]:h-12 [&_svg]:w-12">
                            <x-icon-vkvideo/>
                        </span>
                    </a>
                @endif

            </div>
        </div>
    </div>

    <!-- Нижняя часть -->
    @if(isset($block->payload['theme']))
        <div
            class="w-full flex flex-wrap items-center gap-10 md:text-left z-10 md:pt-6 pl-2">
            <div>
                <p class="text-sm text-interactive text-left">Дата публикации:</p>
                <span class="font-semibold text-interactive mt-1">{{ $block->created_at->format('d.m.Y') }}</span>
            </div>
            <div>
                <p class="text-sm text-interactive text-left">Тема статьи:</p>
                <span class="font-semibold text-interactive mt-1">{{ $block->payload['theme'] }}</span>
            </div>
            <div>
                <p class="text-sm text-interactive text-left">Дата обновления:</p>
                <span class="font-semibold text-interactive mt-1">{{ $block->updated_at->format('d.m.Y') }}</span>
            </div>
            <div>
                <p class="text-sm text-interactive text-left">Время на чтение:</p>
                <span class="font-semibold text-interactive mt-1">
                    {{ $readingTime }} {{ trans_choice('минута|минуты|минут', $readingTime, [], 'ru') }}
                </span>
            </div>
            <div>
                <p class="text-sm text-interactive text-left">Просмотры:</p>
                <x-article-views-counter
                    :page="$block->page_id"
                    class="text-base text-interactive"
                />
            </div>
        </div>
    @endif
    
</div>
