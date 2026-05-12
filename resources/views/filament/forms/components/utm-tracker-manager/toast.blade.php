    <div
        x-show="toast.visible"
        x-cloak
        x-transition.opacity.duration.150ms
        class="pointer-events-none fixed right-5 top-5 z-50 max-w-md rounded-lg border border-emerald-200 bg-white px-4 py-3 text-sm shadow-lg dark:border-emerald-500/30 dark:bg-gray-900"
        role="status"
        aria-live="polite"
    >
        <div class="font-medium text-gray-900 dark:text-white" x-text="toast.title"></div>
        <div x-show="toast.message" class="mt-0.5 break-all text-xs text-gray-600 dark:text-gray-300" x-text="toast.message"></div>
    </div>
