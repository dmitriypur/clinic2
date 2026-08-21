@php($details = $currentCity->details_items)

<div class="text-xs md:text-lg font-medium md:text-center">
    © {{ date('Y') }} {{ $details[0]['name'] }}
</div>
