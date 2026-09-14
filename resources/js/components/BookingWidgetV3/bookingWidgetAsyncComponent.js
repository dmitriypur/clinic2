export const BookingWidgetLoading = {
  name: "BookingWidgetLoading",
  props: {
    open: {
      type: Boolean,
      default: false,
    },
  },
  template: `
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      role="status"
      aria-live="polite"
    >
      <div class="flex min-h-40 w-full max-w-md flex-col items-center justify-center gap-3 rounded-20 bg-white p-6 text-center shadow-lg">
        <span class="h-8 w-8 animate-spin rounded-full border-2 border-surface-subdued border-t-action-primary"></span>
        <p class="font-semibold text-heading">Загружаем запись на приём...</p>
      </div>
    </div>
  `,
};

export const BookingWidgetLoadError = {
  name: "BookingWidgetLoadError",
  props: {
    open: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    reloadPage() {
      window.location.reload();
    },
  },
  template: `
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="booking-widget-load-error-title"
    >
      <div class="w-full max-w-md rounded-20 bg-white p-6 text-center shadow-lg">
        <h2 id="booking-widget-load-error-title" class="text-xl font-semibold text-heading">
          Не удалось загрузить запись
        </h2>
        <p class="mt-3 text-sm text-body">
          Проверьте подключение к интернету и повторите попытку.
        </p>
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
          <button type="button" class="btn btn-secondary-outline" @click="$emit('close')">
            Закрыть
          </button>
          <button type="button" class="btn btn-primary" @click="reloadPage">
            Обновить страницу
          </button>
        </div>
      </div>
    </div>
  `,
};

export function createBookingWidgetV3AsyncComponent(loadComponent) {
  return () => ({
    component: loadComponent(),
    loading: BookingWidgetLoading,
    error: BookingWidgetLoadError,
    delay: 0,
    timeout: 15_000,
  });
}
