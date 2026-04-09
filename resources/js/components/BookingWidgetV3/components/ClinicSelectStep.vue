<template>
  <div class="bg-white md:p-6">
    <StepHeader :chipText="stepChipText" @close="$emit('close')">
      Выберите адрес
    </StepHeader>

    <div class="mt-6 h-[206px] overflow-y-auto pr-1 md:min-w-[444px]">
      <button
        v-for="branch in branches"
        :key="branch.id"
        class="relative flex w-full items-center gap-2 rounded-xl border-2 bg-white p-4 md:px-6 md:py-5 text-left mt-2 first:mt-0 before:content-none"
        :class="{
          'border-action-primary': selectedBranchId === branch.id,
          'border-surface-subdued': selectedBranchId !== branch.id,
        }"
        @click="$emit('select-branch', branch)"
      >
        <div v-if="branchMetro(branch)" class="flex items-center gap-2 text-xs font-semibold">
          <img :src="metroIconSrc()" alt="Иконка метро" class="w-3.5 h-3.5">
        </div>
        <span class="block text-sm md:text-base font-semibold text-interactive">
          {{ branchMetro(branch) ? branchMetro(branch) + ', ' : '' }}{{ branchAddressLine(branch) }}
        </span>
        <span v-if="selectedBranchId === branch.id" class="hidden md:block text-interactive leading-none absolute right-2">
          <IconCheck class="w-4 h-4 md:w-6 md:h-6" />
        </span>
      </button>
    </div>

    <div v-if="loading" class="mt-4 text-sm text-interactive">Загрузка...</div>

    <div class="mt-6 flex flex-col-reverse md:flex-row gap-4">
      <SecondaryButton @click="$emit('back')">Назад</SecondaryButton>
      <PrimaryButton :disabled="!selectedBranchId" @click="$emit('next')">
        Продолжить
      </PrimaryButton>
    </div>
  </div>
</template>

<script>
import { getBranchAddressLine, getBranchMetro } from "../utils/branchUtils";

const METRO_ICON_SRC = "/images/metro2.webp";
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
    stepChipText: {
      type: String,
      default: "Шаг №2",
    },
  },
  methods: {
    metroIconSrc() {
      return METRO_ICON_SRC;
    },
    branchAddressLine(branch) {
      return getBranchAddressLine(branch);
    },
    branchMetro(branch) {
      return getBranchMetro(branch);
    },
  },
};
</script>
