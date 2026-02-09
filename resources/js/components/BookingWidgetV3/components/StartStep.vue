<template>
  <div class="bg-white rounded-[24px] px-10 pt-10 pb-10">
    <StepHeader chipText="Шаг №1" @close="$emit('close')">
      Что вам удобнее?
    </StepHeader>

    <div class="mt-10 flex gap-8">
      <button
        class="flex-1 rounded-[12px] border border-[#EBF0F3] bg-white px-6 py-5 text-left text-[16px] font-semibold text-[#1F3462]"
        @click="$emit('select-mode', 'doctor')"
      >
        Выбрать врача
      </button>
      <button
        class="flex-1 rounded-[12px] border border-[#EBF0F3] bg-white px-6 py-5 text-left text-[16px] font-semibold text-[#1F3462]"
        @click="$emit('select-mode', 'clinic')"
      >
        Выбрать клинику
      </button>
    </div>

    <div class="mt-10 text-center text-[14px] text-[#1F3462]">
      <button
        class="inline-flex items-center gap-2 text-[#1F3462]"
        @click="$emit('leave-request')"
      >
        <span class="inline-block h-[18px] w-[18px]">☝</span>
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
