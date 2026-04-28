<button
    {{ $attributes->merge(['class' => 'flex-1 rounded-xl border border-interactive p-3 font-semibold text-interactive hover:text-white hover:border-action-primary btn-white']) }}>
    {{ $slot }}
</button>
