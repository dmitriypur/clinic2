<template>
  <div class="bg-white md:p-6">
    <StepHeader chipText="Шаг №2" @close="$emit('close')">
      Выберите адрес
    </StepHeader>

    <div class="mt-6 h-[206px] overflow-y-auto pr-1 md:min-w-[444px]">
      <button
        v-for="branch in branches"
        :key="branch.id"
        class="flex w-full items-center justify-between rounded-xl border-2 bg-white px-6 py-5 text-left mt-2 first:mt-0 before:-z-40"
        :class="{
          'border-action-primary': selectedBranchId === branch.id,
          'border-surface-subdued': selectedBranchId !== branch.id,
        }"
        @click="$emit('select-branch', branch)"
      >
        <span class="block text-sm md:text-base font-semibold text-interactive">
          {{ branch.address || branch.name || branch.title || "Филиал" }}
        </span>
        <span v-if="selectedBranchId === branch.id" class="text-interactive leading-none">
          <IconCheck class="w-4 h-4 md:w-6 md:h-6" />
        </span>
      </button>
    </div>

    <div v-if="loading" class="mt-4 text-sm text-interactive">Загрузка...</div>

    <div class="mt-6 flex flex-col md:flex-row gap-4">
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
const IconCheck = () => import("./shared/IconCheck.vue");


export default {
  components: { StepHeader, PrimaryButton, SecondaryButton, IconCheck },
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
