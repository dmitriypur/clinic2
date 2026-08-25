<div class="container">
     @if(!$block->title_hidden)
        <div class="mx-auto p-4 md:rounded-2xl md:p-6">
            <h2 class="text-center text-2xl font-semibold text-heading md:text-3xl">
                {{ $block->title }}
            </h2>
        </div>
    @endif

    @if(! empty($contacts) || $imageUrl)
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mt-3 md:mt-5">
            @foreach($contacts as $contact)
                <article class="overflow-hidden rounded-3xl bg-white p-6 text-heading md:p-10">
                    @if(filled($contact['title']))
                        <h3 class="text-xl font-semibold leading-tight md:text-2xl">
                            {{ $contact['title'] }}
                        </h3>
                    @endif

                    @if(! empty($contact['details']))
                        <dl class="mt-4 divide-y divide-dotted divide-gray-300 text-base leading-snug">
                            @foreach($contact['details'] as $detail)
                                <div class="py-4 first:pt-0 last:pb-0">
                                    @if($detail['heading'])
                                        <dt class="font-semibold">{{ $detail['label'] }}</dt>
                                    @else
                                        <dt class="font-semibold leading-tight">{{ $detail['label'] }}</dt>
                                        <dd class="mt-1 break-words">
                                            @if($detail['url'])
                                                <a class="text-action-primary underline hover:text-action-primary-hovered"
                                                   href="{{ $detail['url'] }}">
                                                    {{ $detail['value'] }}
                                                </a>
                                            @else
                                                {{ $detail['value'] }}
                                            @endif
                                        </dd>
                                    @endif
                                </div>
                            @endforeach
                        </dl>
                    @elseif(filled($contact['rawInfo']))
                        <div class="mt-4 break-words text-base leading-snug [&_a]:text-action-primary [&_a]:underline [&_li]:mt-2">
                            {!! $contact['rawInfo'] !!}
                        </div>
                    @endif
                </article>
            @endforeach

            @if($imageUrl)
                <div class="relative hidden md:flex min-h-96 flex-col overflow-hidden rounded-3xl bg-action-primary-light pt-8 md:min-h-full md:pt-12">
                    <img class="relative z-10 mx-auto h-auto w-3/5 max-w-sm"
                         src="{{ asset('images/logo.svg') }}"
                         alt="Ангелы зрения">
                    <img class="mt-auto h-auto max-h-full w-full object-contain object-bottom"
                         src="{{ $imageUrl }}"
                         alt=""
                         loading="lazy">
                </div>
            @endif
        </div>
    @endif
</div>
