<template>
  <section class="booking-service-unavailable relative h-[445px] w-full overflow-hidden bg-white text-interactive md:h-[459px] md:w-[600px]">
    <button
      type="button"
      class="absolute right-[14px] top-[14px] z-10 flex h-6 w-6 items-center justify-center bg-transparent md:right-6 md:top-6"
      aria-label="Закрыть"
      @click="$emit('close')"
    >
      <img
        :src="closeIconSrc"
        alt=""
        class="h-[18px] w-[18px]"
        aria-hidden="true"
      />
    </button>

    <img
      :src="illustrationSrc"
      alt=""
      class="absolute left-1/2 top-6 h-[120px] w-[120px] -translate-x-1/2 md:top-10"
      aria-hidden="true"
    />

    <h2 class="absolute left-1/2 top-[159px] w-[320px] -translate-x-1/2 text-center text-[28px] font-semibold leading-[1.2] md:top-[176px] md:w-[411px] md:text-[34px]">
      К сожалению, произошла ошибка
    </h2>

    <p class="absolute left-1/2 top-[244px] w-[331px] -translate-x-1/2 text-center text-base leading-[1.2] md:top-[274px]">
      Онлайн-запись временно недоступна. Скоро вам позвонит наш специалист для&nbsp;оформления записи
    </p>

    <div class="error-actions absolute bottom-6 left-4 flex w-[calc(100%-32px)] flex-col items-center gap-6 md:bottom-auto md:left-[78px] md:top-[367px] md:w-[444px] md:flex-row md:gap-4">
      <button
        type="button"
        class="error-actions__back h-auto w-full rounded-[10px] font-semibold leading-[1.22] text-interactive underline md:h-[52px] md:flex-1 md:border md:border-interactive md:no-underline"
        @click="$emit('back')"
      >
        Назад
      </button>

      <button
        type="button"
        class="error-actions__close btn-gradient h-[52px] w-full rounded-[10px] font-semibold text-white md:flex-1"
        @click="$emit('close')"
      >
        Закрыть
      </button>
    </div>
  </section>
</template>

<script>
const ASSET_BASE = "/images/booking-widget-v3";

export default {
  data() {
    return {
      closeIconSrc: `${ASSET_BASE}/close-dark-blue.svg`,
      illustrationSrc: `${ASSET_BASE}/service-unavailable.png`,
    };
  },
};
</script>

<style scoped>
.error-actions__close {
  order: 1;
}

.error-actions__back {
  order: 2;
}

@media (min-width: 768px) {
  .error-actions__back {
    order: 1;
  }

  .error-actions__close {
    order: 2;
  }
}
</style>
