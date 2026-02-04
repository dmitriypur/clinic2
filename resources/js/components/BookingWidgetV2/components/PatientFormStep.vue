<template>
  <div class="p-6 md:p-8">
    <div class="flex items-center justify-between mb-6">
      <button
        class="bg-white border border-interactive text-interactive hover:bg-interactive/5 h-8 text-sm transition-colors rounded-lg font-medium px-3"
        @click="$emit('back')"
      >
        Назад
      </button>
      <h2 class="text-lg md:text-xl font-semibold text-heading">
        Данные пациента
      </h2>
      <div class="w-16"></div>
    </div>

    <!-- Appointment Summary -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200 space-y-2">
      <div class="flex items-center gap-2">
        <svg class="size-5 text-interactive flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
            clip-rule="evenodd"
          />
        </svg>
        <p class="font-medium text-heading">{{ selectedDoctor?.full_name }}</p>
      </div>
      <div class="flex items-center gap-2">
        <svg class="size-5 text-interactive flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
            clip-rule="evenodd"
          />
        </svg>
        <p class="text-subdued">{{ formattedDateTime }}</p>
      </div>
      <div v-if="selectedSlot?.clinic_name" class="flex items-center gap-2">
        <svg class="size-5 text-interactive flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
            clip-rule="evenodd"
          />
        </svg>
        <p class="text-subdued text-sm">
          {{ selectedSlot.clinic_name }}
          <span v-if="selectedSlot.branch_name">— {{ selectedSlot.branch_name }}</span>
        </p>
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Full Name -->
      <div>
        <label class="block text-sm font-medium text-heading mb-1">
          ФИО пациента <span class="text-critical">*</span>
        </label>
        <input
          v-model="form.full_name"
          type="text"
          placeholder="Иванов Иван Иванович"
          required
          class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-interactive"
          :class="{
            'border-critical': errors.full_name,
            'border-gray-300': !errors.full_name,
          }"
        />
        <p v-if="errors.full_name" class="mt-1 text-sm text-critical">
          {{ errors.full_name }}
        </p>
      </div>

      <!-- Parent Full Name (for children) -->
      <div v-if="showParentField">
        <label class="block text-sm font-medium text-heading mb-1">
          ФИО родителя
        </label>
        <input
          v-model="form.full_name_parent"
          type="text"
          placeholder="Иванов Иван Иванович"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-interactive"
        />
      </div>

      <!-- Birth Date -->
      <div>
        <label class="block text-sm font-medium text-heading mb-1">
          Дата рождения <span class="text-critical">*</span>
        </label>
        <input
          v-model="form.birth_date"
          type="date"
          required
          :max="maxBirthDate"
          class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-interactive"
          :class="{
            'border-critical': errors.birth_date,
            'border-gray-300': !errors.birth_date,
          }"
        />
        <p v-if="errors.birth_date" class="mt-1 text-sm text-critical">
          {{ errors.birth_date }}
        </p>
      </div>

      <!-- Phone -->
      <div>
        <label class="block text-sm font-medium text-heading mb-1">
          Телефон <span class="text-critical">*</span>
        </label>
        <input
          v-model="form.phone"
          v-mask="'+7 (###) ###-##-##'"
          type="tel"
          placeholder="+7 (999) 999-99-99"
          required
          class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-interactive"
          :class="{
            'border-critical': errors.phone,
            'border-gray-300': !errors.phone,
          }"
        />
        <p v-if="errors.phone" class="mt-1 text-sm text-critical">
          {{ errors.phone }}
        </p>
      </div>

      <!-- Promo Code (optional) -->
      <div>
        <label class="block text-sm font-medium text-heading mb-1">
          Промокод (при наличии)
        </label>
        <input
          v-model="form.promo_code"
          type="text"
          placeholder="Введите промокод"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-interactive"
        />
      </div>

      <!-- Comment (optional) -->
      <div>
        <label class="block text-sm font-medium text-heading mb-1">
          Комментарий
        </label>
        <textarea
          v-model="form.comment"
          rows="3"
          placeholder="Дополнительная информация"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-interactive resize-none"
        ></textarea>
      </div>

      <!-- Privacy Policy -->
      <div>
        <div class="relative flex gap-x-3">
          <div class="flex h-6 items-center">
            <input
              v-model="form.privacy"
              type="checkbox"
              required
              class="h-4 w-4 rounded bg-transparent border border-default text-interactive checked:bg-interactive focus:ring-2 focus:ring-interactive"
            />
          </div>
          <div class="text-sm leading-6">
            <label class="font-medium select-none">
              Оставляя заявку, я соглашаюсь на использование
              <a
                href="/documents"
                target="_blank"
                class="text-interactive underline hover:no-underline"
              >
                персональных данных
              </a>
              <span class="text-critical">*</span>
            </label>
          </div>
        </div>
        <p v-if="errors.privacy" class="mt-2 text-sm text-critical">
          {{ errors.privacy }}
        </p>
      </div>

      <!-- General Error -->
      <div v-if="generalError" class="p-3 bg-critical/10 border border-critical rounded-lg">
        <p class="text-sm text-critical">{{ generalError }}</p>
      </div>

      <!-- Submit Button -->
      <div class="pt-4">
        <button
          type="submit"
          :disabled="isSubmitting"
          class="w-full p-3 lg:px-6 font-semibold rounded text-white transition-colors shadow-md flex items-center justify-center"
          :class="{
            'bg-interactive hover:bg-interactive-button-hovered active:bg-interactive-button-hovered': !isSubmitting,
            'bg-disabled text-disabled cursor-not-allowed': isSubmitting,
          }"
        >
          <svg
            v-if="isSubmitting"
            class="animate-spin h-5 w-5 mr-2"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          <span>{{ isSubmitting ? 'Отправка...' : 'Записаться на приём' }}</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script>
export default {
  name: 'PatientFormStep',

  props: {
    selectedDoctor: {
      type: Object,
      default: null,
    },
    selectedDate: {
      type: Date,
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
        full_name: '',
        full_name_parent: '',
        birth_date: '',
        phone: '',
        promo_code: '',
        comment: '',
        privacy: false,
      },
      errors: {},
      generalError: null,
      showParentField: false,
    };
  },

  computed: {
    formattedDateTime() {
      if (!this.selectedDate || !this.selectedSlot) return '';
      
      const dateOptions = { day: 'numeric', month: 'long', year: 'numeric' };
      const dateStr = this.selectedDate.toLocaleDateString('ru-RU', dateOptions);
      
      return `${dateStr} в ${this.selectedSlot.time}`;
    },

    maxBirthDate() {
      // Сегодняшняя дата
      return new Date().toISOString().split('T')[0];
    },
  },

  methods: {
    handleSubmit() {
      this.errors = {};
      this.generalError = null;

      // Валидация
      if (!this.validateForm()) {
        return;
      }

      // Эмитим данные формы наверх
      this.$emit('submit', { ...this.form });
    },

    validateForm() {
      let isValid = true;

      // ФИО
      if (!this.form.full_name || this.form.full_name.trim().length < 3) {
        this.errors.full_name = 'Введите полное ФИО';
        isValid = false;
      }

      // Дата рождения
      if (!this.form.birth_date) {
        this.errors.birth_date = 'Укажите дату рождения';
        isValid = false;
      }

      // Телефон
      if (!this.form.phone || this.form.phone.replace(/\D/g, '').length < 11) {
        this.errors.phone = 'Введите корректный номер телефона';
        isValid = false;
      }

      // Согласие
      if (!this.form.privacy) {
        this.errors.privacy = 'Необходимо согласие на обработку данных';
        isValid = false;
      }

      return isValid;
    },

    setErrors(apiErrors) {
      this.errors = apiErrors || {};
    },

    setGeneralError(message) {
      this.generalError = message;
    },

    calculateAge(dateStr) {
      if (!dateStr) {
        return 0;
      }
      const [year, month, day] = dateStr.split('-').map((v) => parseInt(v, 10));
      if (!year || !month || !day) {
        return 0;
      }
      const today = new Date();
      let age = today.getFullYear() - year;
      const m = today.getMonth() + 1 - month;
      const d = today.getDate() - day;
      if (m < 0 || (m === 0 && d < 0)) {
        age--;
      }
      return age;
    },
  },

  watch: {
    'form.birth_date'(newVal) {
      if (newVal) {
        const age = this.calculateAge(newVal);
        this.showParentField = age < 18;
      }
    },
  },
};
</script>
