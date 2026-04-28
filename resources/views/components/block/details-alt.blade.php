@php($details = $currentCity->details_items)
@php($hasSingleDetail = count($details) === 1)

@if(count($details))
    <div class="container">
        @if(!$block->title_hidden)
            <div class="mx-auto px-10 mb-6 md:mb-12">
                <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
                    {{ $block->title }}
                </h2>
            </div>
        @endif

        <div @class([
            'grid gap-6',
            'lg:grid-cols-2' => !$hasSingleDetail,
        ])>
            @foreach($details as $detail)
                <article class="rounded-3xl bg-white p-6 text-heading md:p-10">
                    @if($detail['name'])
                        <h3 class="text-2xl font-semibold leading-7">
                            {{ $detail['name'] }}
                        </h3>
                    @endif

                    @if(count($detail['rows']))
                        <div @class([
                            'mt-6 text-base',
                            'space-y-4' => !$hasSingleDetail,
                            'grid gap-x-16 gap-y-4 md:grid-cols-2' => $hasSingleDetail,
                        ])>
                            @foreach($hasSingleDetail ? $detail['columns'] : [$detail['rows']] as $column)
                                <div class="space-y-4">
                                    @foreach($column as $row)
                                        <div @class([
                                            'border-t border-dashed border-interactive/30 pt-4' => !$loop->first,
                                        ])>
                                            <div class="font-semibold leading-5">
                                                {{ $row['label'] }}
                                            </div>
                                            <div class="mt-1 leading-5">
                                                {{ $row['value'] }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
@endif
