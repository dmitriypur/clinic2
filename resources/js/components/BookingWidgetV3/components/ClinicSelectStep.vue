<template>
  <div class="bg-white rounded-[24px] px-10 pt-10 pb-10">
    <StepHeader chipText="Шаг №2" @close="$emit('close')">
      Выберите адрес
    </StepHeader>

    <div class="mt-6 h-[206px] overflow-y-auto pr-2">
      <button
        v-for="branch in branches"
        :key="branch.id"
        class="flex w-full items-center justify-between rounded-[12px] border border-[#EBF0F3] bg-white px-6 py-4 text-left"
        :class="{
          'border-2 border-[#FF8212] bg-[#FFF5EB]': selectedBranchId === branch.id,
          'border border-[#EBF0F3]': selectedBranchId !== branch.id,
        }"
        @click="$emit('select-branch', branch)"
      >
        <span class="text-[16px] font-semibold text-[#1F3462]">
          {{ branch.address || branch.name || branch.title || "Филиал" }}
        </span>
        <span v-if="selectedBranchId === branch.id" class="text-[#1F3462]">✓</span>
      </button>
    </div>

    <div v-if="loading" class="mt-4 text-sm text-[#1F3462]">Загрузка...</div>

    <div class="mt-6 flex gap-4">
      <SecondaryButton @click="$emit('back')">Назад</SecondaryButton>
      <PrimaryButton :disabled="!selectedBranchId" @click="$emit('next')">
        Продолжить
      </PrimaryButton>
    </div>
  </div>
</template>

<script>
const StepHeader = () => import("./shared/StepHeader.vue");
const PrimaryButton = () => import("./shared/PrimaryButton.vue");
const SecondaryButton = () => import("./shared/SecondaryButton.vue");

export default {
  components: { StepHeader, PrimaryButton, SecondaryButton },
  props: {
    branches: {
      type: Array,
      default: () => [],
    },
    selectedBranchId: {
      type: [String, Number],
      default: null,
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
};
</script>
