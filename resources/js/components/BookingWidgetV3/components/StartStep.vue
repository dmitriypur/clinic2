<template>
  <div class="bg-white md:py-4">
    <StepHeader chipText="Шаг №1" @close="$emit('close')">
      Что вам удобнее?
    </StepHeader>

    <div class="mt-10 flex flex-col md:flex-row gap-8">
      <button
        class="btn-white rounded-xl border border-interactive bg-white p-4 font-semibold text-interactive w-full min-w-64 hover:text-white hover:border-action-primary"
        @click="$emit('select-mode', 'doctor')"
      >
        Выбрать врача
      </button>
      <button
        class="btn-white rounded-xl border border-interactive bg-white p-4 font-semibold text-interactive w-full min-w-64 hover:text-white hover:border-action-primary"
        @click="$emit('select-mode', 'clinic')"
      >
        Выбрать клинику
      </button>
    </div>

    <div class="mt-10 text-center">
      <button
        class="text-interactive font-semibold underline"
        @click="$emit('leave-request')"
      >
        Хочу оставить заявку
      </button>
    </div>
  </div>
</template>

<script>
const StepHeader = () => import("./shared/StepHeader.vue");

export default {
  components: { StepHeader },
  props: {
    mode: {
      type: String,
      default: null,
    },
  },
};
</script>
