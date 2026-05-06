<template>
  <div class="bg-white md:px-12 md:py-6">
    <StepHeader :chipText="stepChipText" @close="$emit('close')">
      Заполните данные
    </StepHeader>

    <div class="mt-6 grid grid-cols-1 gap-4">
      <div>
        <input
          v-model="form.last_name"
          class="w-full rounded-lg text-sm px-6 py-4 focus:outline-action-primary focus:bg-white"
          :class="errors.last_name || errors.full_name ? 'border border-[#E04F4F]' : 'bg-surface-subdued'"
          placeholder="Фамилия"
        />
        <p v-if="errors.last_name || errors.full_name" class="mt-1 text-xs text-[#E04F4F]">
          {{ errors.last_name || errors.full_name }}
        </p>
      </div>

      <div>
        <input
          v-model="form.first_name"
          class="w-full rounded-lg text-sm px-6 py-4 focus:outline-action-primary focus:bg-white"
          :class="errors.first_name || errors.full_name ? 'border border-[#E04F4F] bg-white' : 'bg-surface-subdued'"
          placeholder="Имя"
        />
        <p v-if="errors.first_name" class="mt-1 text-xs text-[#E04F4F]">
          {{ errors.first_name }}
        </p>
      </div>

      <div>
        <input
          v-model="form.middle_name"
          class="w-full rounded-lg text-sm px-6 py-4 bg-surface-subdued focus:outline-action-primary focus:bg-white"
          placeholder="Отчество (необязательно)"
        />
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <input
            v-model="form.phone"
            v-mask="'+7 (###) ###-##-##'"
            type="tel"
            class="w-full rounded-lg text-sm px-6 py-4 focus:outline-action-primary focus:bg-white"
            :class="errors.phone ? 'border border-[#E04F4F] bg-white' : 'border border-transparent bg-surface-subdued'"
            placeholder="+7 (___) ___-__-__"
          />
          <p v-if="errors.phone" class="mt-1 text-xs text-[#E04F4F]">
            {{ errors.phone }}
          </p>
        </div>
        <div>
          <input
            v-model="form.birth_date"
            type="date"
            :max="maxBirthDate"
            :disabled="birthDateReadonly"
            class="w-full rounded-lg text-sm px-6 py-4 focus:outline-action-primary"
            :class="birthDateInputClasses"
            placeholder="Дата рождения"
          />
          <p v-if="errors.birth_date" class="mt-1 text-xs text-[#E04F4F]">
            {{ errors.birth_date }}
          </p>
        </div>
      </div>
    </div>

    <div class="mt-6 flex items-center gap-4 md:mx-auto max-w-[320px]">
      <input id="privacy" v-model="form.privacy" type="checkbox" class="h-10 w-10" />
      <label for="privacy" class="text-sm text-interactive">
        Оставляя заявку, я соглашаюсь на использование персональных данных
      </label>
    </div>
    <p v-if="errors.privacy" class="mt-1 text-xs text-[#E04F4F]">
      {{ errors.privacy }}
    </p>

    <div v-if="generalError" class="mt-4 rounded-[10px] border border-[#E04F4F] bg-[#FDEEEE] px-4 py-3 text-sm text-[#A52A2A]">
      {{ generalError }}
    </div>

    <div class="mx-auto mt-6 md:mt-8 flex flex-col-reverse md:flex-row w-full max-w-[444px] gap-4">
      <SecondaryButton @click="$emit('back')">Назад</SecondaryButton>
      <PrimaryButton :disabled="isSubmitting" @click="handleSubmit">
        {{ isSubmitting ? "Отправка..." : "Записаться" }}
      </PrimaryButton>
    </div>
  </div>
</template>

<script>
import {
  formatDateForInput,
} from "../utils/doctorAgeUtils";

const StepHeader = () => import("./shared/StepHeader.vue");
const PrimaryButton = () => import("./shared/PrimaryButton.vue");
const SecondaryButton = () => import("./shared/SecondaryButton.vue");

export default {
  components: { StepHeader, PrimaryButton, SecondaryButton },
  props: {
    selectedDoctor: {
      type: Object,
      default: null,
    },
    selectedClinic: {
      type: Object,
      default: null,
    },
    selectedBranch: {
      type: Object,
      default: null,
    },
    selectedDate: {
      type: [Date, null],
      default: null,
    },
    selectedSlot: {
      type: Object,
      default: null,
    },
    isSubmitting: {
      type: Boolean,
      default: false,
    },
    initialBirthDate: {
      type: String,
      default: "",
    },
    initialPatientData: {
      type: Object,
      default: null,
    },
    birthDateReadonly: {
      type: Boolean,
      default: true,
    },
    stepChipText: {
      type: String,
      default: "Шаг №4",
    },
  },
  data() {
    return {
      form: {
        last_name: "",
        first_name: "",
        middle_name: "",
        full_name_parent: "",
        birth_date: this.initialBirthDate || "",
        phone: "",
        promo_code: "",
        comment: "",
        privacy: false,
      },
      errors: {},
      generalError: null,
      showParentField: false,
    };
  },
  computed: {
    maxBirthDate() {
      return formatDateForInput(new Date());
    },
    birthDateInputClasses() {
      return [
        this.errors.birth_date ? "border border-[#E04F4F]" : "border border-transparent",
        this.birthDateReadonly
          ? "bg-surface-subdued text-interactive/70 cursor-not-allowed"
          : "bg-surface-subdued focus:bg-white",
      ];
    },
    formattedAppointment() {
      if (!this.selectedDate || !this.selectedSlot?.time) {
        return "Дата и время не выбраны";
      }
      const date = this.selectedDate.toLocaleDateString("ru-RU", {
        day: "numeric",
        month: "long",
        year: "numeric",
      });
      return `${date} в ${this.selectedSlot.time}`;
    },
  },
  methods: {
    applyInitialPatientData(data) {
      if (!data || typeof data !== "object") {
        return;
      }

      const nextLastName = String(data.last_name || "").trim();
      if (!String(this.form.last_name || "").trim() && nextLastName) {
        this.form.last_name = nextLastName;
      }

      const nextFirstName = String(data.first_name || "").trim();
      if (!String(this.form.first_name || "").trim() && nextFirstName) {
        this.form.first_name = nextFirstName;
      }

      const nextMiddleName = String(data.middle_name || "").trim();
      if (!String(this.form.middle_name || "").trim() && nextMiddleName) {
        this.form.middle_name = nextMiddleName;
      }

      const nextPhone = String(data.phone_number || "").trim();
      if (!String(this.form.phone || "").trim() && nextPhone) {
        this.form.phone = nextPhone;
      }
    },
    handleSubmit() {
      this.errors = {};
      this.generalError = null;

      if (!this.validateForm()) {
        return;
      }

      this.$emit("submit", {
        full_name: this.buildFullName(),
        full_name_parent: this.form.full_name_parent,
        birth_date: this.form.birth_date,
        phone: this.form.phone,
        promo_code: this.form.promo_code,
        comment: this.form.comment,
      });
    },
    validateForm() {
      let isValid = true;

      if (!this.form.last_name || this.form.last_name.trim().length < 2) {
        this.errors.last_name = "Введите фамилию";
        isValid = false;
      }

      if (!this.form.first_name || this.form.first_name.trim().length < 2) {
        this.errors.first_name = "Введите имя";
        isValid = false;
      }

      if (!this.form.birth_date) {
        this.errors.birth_date = "Укажите дату рождения";
        isValid = false;
      }

      if (!this.form.phone || this.form.phone.replace(/\D/g, "").length < 11) {
        this.errors.phone = "Введите корректный телефон";
        isValid = false;
      }

      if (!this.form.privacy) {
        this.errors.privacy = "Необходимо согласие на обработку данных";
        isValid = false;
      }

      return isValid;
    },
    buildFullName() {
      return [this.form.last_name, this.form.first_name, this.form.middle_name]
        .map((part) => String(part || "").trim())
        .filter(Boolean)
        .join(" ");
    },
    setErrors(apiErrors) {
      const normalized = {};
      Object.entries(apiErrors || {}).forEach(([key, value]) => {
        normalized[key] = Array.isArray(value) ? value[0] : value;
      });
      this.errors = normalized;
    },
    setGeneralError(message) {
      this.generalError = message;
    },
  },
  watch: {
    initialBirthDate(value) {
      this.form.birth_date = value || "";
    },
    initialPatientData: {
      handler(value) {
        this.applyInitialPatientData(value);
      },
      immediate: true,
      deep: true,
    },
  },
};
</script>
