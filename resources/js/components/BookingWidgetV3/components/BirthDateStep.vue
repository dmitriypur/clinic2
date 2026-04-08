<template>
  <div class="bg-white p-2 md:py-6 md:px-14">
      <StepHeader @close="$emit('close')">
         Дата рождения пациента
      </StepHeader>

      <div class="mt-6 md:mt-10">
        <input
          v-model="form.birthDate"
          v-mask="'##.##.####'"
          type="text"
          inputmode="numeric"
          autocomplete="bday"
          class="h-[60px] w-full rounded-[12px] border border-transparent bg-surface-subdued px-6 text-base text-interactive outline-none transition-colors placeholder:text-[#B2BAC6] focus:border-action-primary"
          :class="error ? 'border border-[#E04F4F]' : ''"
          placeholder="ДД.ММ.ГГГГ"
          @keyup.enter="handleNext"
        />
        <p v-if="error" class="mt-2 text-xs text-[#E04F4F]">
          {{ error }}
        </p>
      </div>

      <div class="mt-8 hidden gap-4 md:flex">
        <SecondaryButton
          v-if="showBackButton"
          :disabled="backDisabled"
          @click="handleBack"
        >
          Назад
        </SecondaryButton>
        <PrimaryButton @click="handleNext">Далее</PrimaryButton>
      </div>

      <div class="mt-6 flex flex-col md:hidden">
        <PrimaryButton @click="handleNext">Далее</PrimaryButton>
        <button
          v-if="showBackButton"
          type="button"
          class="text-base font-semibold underline underline-offset-4 mt-6"
          :class="backDisabled ? 'cursor-default text-interactive/40 no-underline' : 'text-interactive'"
          :disabled="backDisabled"
          @click="handleBack"
        >
          Назад
        </button>
      </div>

  </div>
</template>

<script>
import {
  birthDateDisplayToIso,
  validateBirthDateDisplay,
} from "../utils/birthDate";

const StepHeader = () => import("./shared/StepHeader.vue");
const PrimaryButton = () => import("./shared/PrimaryButton.vue");
const SecondaryButton = () => import("./shared/SecondaryButton.vue");

export default {
  name: "BirthDateStep",
  components: {
    PrimaryButton,
    SecondaryButton,
    StepHeader,
  },
  props: {
    initialValue: {
      type: String,
      default: "",
    },
    showBackButton: {
      type: Boolean,
      default: true,
    },
    backDisabled: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      form: {
        birthDate: this.initialValue || "",
      },
      error: "",
    };
  },
  methods: {
    handleBack() {
      if (this.backDisabled) {
        return;
      }

      this.$emit("back");
    },
    handleNext() {
      this.error = validateBirthDateDisplay(this.form.birthDate);

      if (this.error) {
        return;
      }

      this.$emit("next", {
        display: this.form.birthDate.trim(),
        iso: birthDateDisplayToIso(this.form.birthDate),
      });
    },
  },
  watch: {
    initialValue(value) {
      this.form.birthDate = value || "";
      this.error = "";
    },
    "form.birthDate"() {
      if (this.error) {
        this.error = "";
      }
    },
  },
};
</script>
