@push('scripts')
    {!! Clinic::schema()->videoItem($block->title, $block->body_html ?? 'Видео', $block->getFirstMediaUrl('cover'), $block->created_at, $block->getFirstMediaUrl('video')) !!}
@endpush
@if(!$block->title_hidden)
    <div class="mx-auto px-10 mb-6 md:mb-12">
        <h2 class="font-semibold text-2xl md:text-4xl text-center text-heading">
            {{ $block->title }}
        </h2>
    </div>
@endif

<div class="lg:container">
    <div class="max-w-5xl w-full mx-auto">
        <video-component video="{{ $block->getFirstMediaUrl('video') }}"
                         poster="{{$block->getFirstMediaUrl('cover')  }}"></video-component>
    </div>
</div>
