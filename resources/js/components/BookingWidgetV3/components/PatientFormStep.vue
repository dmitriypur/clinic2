<template>
  <div class="bg-white rounded-[24px] px-10 pt-10 pb-10">
    <StepHeader chipText="Шаг №4" @close="$emit('close')">
      Заполните данные
    </StepHeader>

    <div class="mt-4 rounded-[12px] border border-[#EBF0F3] bg-[#F6F7F9] px-4 py-3 text-sm text-[#1F3462]">
      <p class="font-semibold">{{ selectedDoctor?.name || selectedDoctor?.full_name || "Врач не выбран" }}</p>
      <p class="mt-1">{{ formattedAppointment }}</p>
      <p v-if="selectedBranch?.address" class="mt-1">{{ selectedBranch.address }}</p>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4">
      <div>
        <input
          v-model="form.last_name"
          class="h-[52px] w-full rounded-[12px] border px-4"
          :class="errors.last_name || errors.full_name ? 'border-[#E04F4F]' : 'border-[#EBF0F3]'"
          placeholder="Фамилия"
        />
        <p v-if="errors.last_name || errors.full_name" class="mt-1 text-xs text-[#E04F4F]">
          {{ errors.last_name || errors.full_name }}
        </p>
      </div>

      <div>
        <input
          v-model="form.first_name"
          class="h-[52px] w-full rounded-[12px] border px-4"
          :class="errors.first_name || errors.full_name ? 'border-[#E04F4F]' : 'border-[#EBF0F3]'"
          placeholder="Имя"
        />
        <p v-if="errors.first_name" class="mt-1 text-xs text-[#E04F4F]">
          {{ errors.first_name }}
        </p>
      </div>

      <div>
        <input
          v-model="form.middle_name"
          class="h-[52px] w-full rounded-[12px] border border-[#EBF0F3] px-4"
          placeholder="Отчество (необязательно)"
        />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <input
            v-model="form.birth_date"
            type="date"
            :max="maxBirthDate"
            class="h-[52px] w-full rounded-[12px] border px-4"
            :class="errors.birth_date ? 'border-[#E04F4F]' : 'border-[#EBF0F3]'"
          />
          <p v-if="errors.birth_date" class="mt-1 text-xs text-[#E04F4F]">
            {{ errors.birth_date }}
          </p>
        </div>

        <div>
          <input
            v-model="form.phone"
            v-mask="'+7 (###) ###-##-##'"
            type="tel"
            class="h-[52px] w-full rounded-[12px] border px-4"
            :class="errors.phone ? 'border-[#E04F4F]' : 'border-[#EBF0F3]'"
            placeholder="+7 (___) ___-__-__"
          />
          <p v-if="errors.phone" class="mt-1 text-xs text-[#E04F4F]">
            {{ errors.phone }}
          </p>
        </div>
      </div>

      <input
        v-if="showParentField"
        v-model="form.full_name_parent"
        class="h-[52px] w-full rounded-[12px] border border-[#EBF0F3] px-4"
        placeholder="ФИО родителя"
      />

      <input
        v-model="form.promo_code"
        class="h-[52px] w-full rounded-[12px] border border-[#EBF0F3] px-4"
        placeholder="Промокод (необязательно)"
      />

      <textarea
        v-model="form.comment"
        rows="3"
        class="w-full rounded-[12px] border border-[#EBF0F3] px-4 py-3"
        placeholder="Комментарий (необязательно)"
      ></textarea>
    </div>

    <div class="mt-6 flex items-start gap-4">
      <input v-model="form.privacy" type="checkbox" class="mt-2 h-5 w-5" />
      <p class="text-[14px] text-[#1F3462]">
        Оставляя заявку, я соглашаюсь на использование персональных данных
      </p>
    </div>
    <p v-if="errors.privacy" class="mt-1 text-xs text-[#E04F4F]">
      {{ errors.privacy }}
    </p>

    <div v-if="generalError" class="mt-4 rounded-[10px] border border-[#E04F4F] bg-[#FDEEEE] px-4 py-3 text-sm text-[#A52A2A]">
      {{ generalError }}
    </div>

    <div class="mt-10 flex gap-4">
      <SecondaryButton @click="$emit('back')">Назад</SecondaryButton>
      <PrimaryButton :disabled="isSubmitting" @click="handleSubmit">
        {{ isSubmitting ? "Отправка..." : "Записаться на приём" }}
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
  },
  data() {
    return {
      form: {
        last_name: "",
        first_name: "",
        middle_name: "",
        full_name_parent: "",
        birth_date: "",
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
      return new Date().toISOString().split("T")[0];
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
    calculateAge(dateStr) {
      const [year, month, day] = String(dateStr || "")
        .split("-")
        .map((v) => parseInt(v, 10));
      if (!year || !month || !day) return 0;
      const today = new Date();
      let age = today.getFullYear() - year;
      const m = today.getMonth() + 1 - month;
      const d = today.getDate() - day;
      if (m < 0 || (m === 0 && d < 0)) {
        age -= 1;
      }
      return age;
    },
  },
  watch: {
    "form.birth_date"(value) {
      if (!value) {
        this.showParentField = false;
        return;
      }
      this.showParentField = this.calculateAge(value) < 18;
    },
  },
};
</script>
