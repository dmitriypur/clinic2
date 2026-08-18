@include('curator::components.forms.picker')

@if (count($getState() ?? []) > 0 && ! $isMultiple())
    <div class="mt-3">
        {{ $getAction('open_curator_picker') }}
    </div>
@endif
