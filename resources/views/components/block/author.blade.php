<div class="container content">
    <div class="flex flex-col md:flex-row items-stretch relative gap-6">
        <!-- Левая часть -->
        @if(isset($block->payload['author']))
            <div
                class="bg-white rounded-lg md:rounded-20 p-4 md:py-6 md:px-10 flex flex-col items-center md:flex-row space-y-4 sm:space-y-0 sm:space-x-4 w-full md:w-1/2">
                <div
                    class="h-16 w-16 lg:h-28 lg:w-28 overflow-hidden rounded-full flex-none border [&_img]:object-cover">
                    {!! $block->author->avatar_image !!}
                </div>
                <div class="text-center sm:text-left">
                    <p class="text-interactive">Автор статьи:</p>
                    @if($block->payload['url'])
                        <a href="{{ city_route('pages.show', ['handle' => $block->payload['url']]) }}"
                           class="text-lg md:text-xl font-semibold text-action-primary underline hover:text-action-primary-hovered">
                            {{ $block->author->full_name }}
                        </a>
                    @else
                        <span class="text-lg md:text-xl font-semibold text-interactive">
                            {{ $block->author->full_name }}
                        </span>
                    @endif
                    <p class="text-gray-700 mt-1">
                        {{ $block->author->speciality }}
                    </p>
                </div>
            </div>

        @endif

        <!-- Правая часть -->
        @if(isset($block->payload['theme']))
            <div
                class="bg-white rounded-lg md:rounded-20 p-4 md:py-6 md:px-10 w-full md:w-1/2 flex text-center md:text-left z-10">
                <div class="ml-4">
                    <p class="text-interactive text-left">Тема статьи:</p>
                    <span class="text-lg md:text-xl font-semibold text-interactive mt-1">{{ $block->payload['theme'] }}</span>
                </div>
            </div>
        @endif
    </div>
</div>
