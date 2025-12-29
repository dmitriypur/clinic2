@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="container">
    <div class="content lg:text-xl text-interactive">
        @if(isset($services) && $services->isNotEmpty())
            <div class="grid lg:grid-cols-2 gap-4 lg:gap-8">
                @foreach($services->chunk(ceil($services->count()/2)) as $chunk)
                    <div class="space-y-4 lg:space-y-8">
                        @foreach($chunk as $service)
                            <div is="service-card" inline-template
                                 :uuid="'{{ $service->uuid }}'">
                                <div
                                    class="rounded-2xl border pb-0 max-w-full scroll-mt-20 scroll-smooth lg:scroll-mt-32"
                                    id="{{ $service->uuid }}">
                                    <div :class="mainCardClassName"
                                         @click="toggle">
                                        <div
                                            class="w-16 h-16 lg:w-20 lg:h-20 rounded-full overflow-hidden flex-none">
                                            <picture
                                                itemscope
                                                itemtype="http://schema.org/ImageObject">
                                                        <span itemprop="name"
                                                              class="hidden">{{ $service->title }}</span>
                                                {{ $service->responsive_image }}
                                            </picture>
                                        </div>
                                        <div class="flex-auto">
                                            <h2 class="font-medium lg:text-2xl text-interactive leading-none lg:leading-normal">
                                                <span
                                                    class="box-decoration-clone border-b border-transparent group-hover:border-interactive">
                                                {{ $service->title }}
                                                </span>
                                            </h2>
                                        </div>
                                        <div
                                            class="p-4 -mr-4 text-interactive">
                                            <span v-if="active"><x-icon-minus/></span>
                                            <span v-else><x-icon-plus/></span>
                                        </div>
                                    </div>
                                    <div :class="className">
                                        @foreach($service->children as $child)
                                            <div
                                                class="flex justify-between items-start border-b py-4 last:border-transparent space-x-4">
                                                <p>{{ $child->title }}</p>
                                                <div class="font-medium whitespace-nowrap text-right">
                                                    @if($child->current_price?->old_price)
                                                        <span class="block text-sm text-gray-400 line-through decoration-red-500/50">
                                                            {{ number_format($child->current_price->old_price, 0, '.', ' ') }} руб.
                                                        </span>
                                                    @endif
                                                    <div>
                                                        @if($child->current_price?->price_from)
                                                            от
                                                        @endif
                                                        {{ number_format($child->current_price?->price ?? 0, 0, '.', ' ') }}
                                                        руб.
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
