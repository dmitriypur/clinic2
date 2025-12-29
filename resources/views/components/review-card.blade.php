<div
    class="mb-5 md:mb-0 rounded-2xl p-4 lg:p-7 bg-white h-full shadow-card-review"
    itemProp="review" itemscope itemType="http://schema.org/Review"
>
    <div class="flex flex-col h-full">
        <div class="hidden" itemprop="itemReviewed" itemscope itemtype="https://schema.org/MedicalOrganization">
            <meta itemProp="name" content="Центр детского зрения Мама, я вижу"/>
            <meta itemProp="address" content="{{ $address }}"/>
        </div>

        <div class="flex flex-wrap items-center gap-2 justify-between mb-5 {{ $review->resource ? '' : 'h-10' }}">
            @if($review->resource)
                <div
                    class="flex items-center rounded-md pr-2 text-base font-medium gap-2 border border-surface-subdued accessibility:border-none">
                    <div
                        class="accessibility:hidden w-auto min-w-10 h-10 px-1 rounded-md bg-surface-subdued flex items-center justify-center">
                        <img src="{{asset($review->resources[1][$review->resource] ?? '')}}"
                             alt="Логотип {{ $review->resources[0][$review->resource] ?? '' }}">
                    </div>
                    {{ $review->resources[0][$review->resource] ?? '' }}
                </div>
            @endif

            @if(!empty($review->doctor))
                <div
                    class="flex flex-shrink-0 items-center justify-center rounded-md bg-surface-subdued p-2 h-10">
                    <p class="text-sm"><span
                            class="text-interactive/60">врач:</span> {{ $review->doctorInitials }}
                    </p>
                </div>
            @endif
        </div>
        <div class="space-y-2">
            <p class="text-disabled font-medium" itemProp="datePublished">{{ $review->get_date ? Carbon\Carbon::parse($review->get_date)->format('d.m.Y') : $review->created_at->format('d.m.Y') }}</p>
            <div class="flex space-x-1.5" itemscope  itemProp="reviewRating" itemType="http://schema.org/Rating">
                <span class="hidden" itemProp="ratingValue">{{ $review->rating }}</span>
                @for($i = 0; $i < $review->rating; $i++)
                    <span
                        class="accessibility:hidden w-5 h-5 text-action-primary">
                                                <x-icon-star-filled/>
                                            </span>
                @endfor
            </div>
        </div>

        <div class="flex flex-col space-y-4 mb-4 mt-2 relative">
            <a href="{{ $review->link_resource }}" target="_blank" itemProp="author" itemType="http://schema.org/Person" itemscope>
                <span class="text-2xl font-semibold" itemProp="name">{{ $review->name }}</span>
            </a>
            <div
                class="content overflow-y-auto max-h-48" itemProp="reviewBody">{!! $review->body_html !!}</div>

        </div>
        <div class="mt-auto flex flex-wrap gap-2">
            @foreach($review->pages as $page)
                @if(isset($block))
                    <div
                        class="bg-surface-subdued py-1 px-3 text-sm inline-block rounded-md">
                        @if($block->page_id != $page->id)
                            <a href="{{ city_route('pages.show', $page->handle) }}">{{$page->title}}</a>
                        @else
                            <p class="text-subdued">{{$page->title}}</p>
                        @endif
                    </div>
                @else
                    <div
                        class="bg-surface-subdued py-1 px-3 text-sm inline-block rounded-md">
                        <a href="{{ city_route('pages.show', $page->handle) }}">{{$page->title}}</a>
                    </div>
                @endif

            @endforeach
        </div>
    </div>
</div>
