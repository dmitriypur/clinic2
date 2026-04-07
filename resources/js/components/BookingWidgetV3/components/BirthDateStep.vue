<template>
  <div class="bg-white px-4 pb-6 pt-4 md:px-12 md:pb-8 md:pt-6">
    <div class="mx-auto w-full max-w-[508px]">
      <h2 class="text-center text-[38px] font-semibold leading-[1.1] text-interactive md:text-[52px]">
        Дата рождения пациента
      </h2>

      <div class="mt-10 md:mt-9">
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

      <div class="mt-6 md:hidden">
        <PrimaryButton @click="handleNext">Далее</PrimaryButton>
      </div>

      <div v-if="showBackButton" class="mt-5 text-center md:hidden">
        <button
          type="button"
          class="text-base font-semibold underline underline-offset-4"
          :class="backDisabled ? 'cursor-default text-interactive/40 no-underline' : 'text-interactive'"
          :disabled="backDisabled"
          @click="handleBack"
        >
          Назад
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import {
  birthDateDisplayToIso,
  validateBirthDateDisplay,
} from "../utils/birthDate";

const PrimaryButton = () => import("./shared/PrimaryButton.vue");
const SecondaryButton = () => import("./shared/SecondaryButton.vue");

export default {
  name: "BirthDateStep",
  components: {
    PrimaryButton,
    SecondaryButton,
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
