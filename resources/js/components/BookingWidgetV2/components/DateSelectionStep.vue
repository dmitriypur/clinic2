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
        Выберите дату приема
      </h2>
      <div class="w-16"></div>
    </div>

    <!-- Selected Doctor Info -->
    <div
      v-if="selectedDoctor"
      class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200"
    >
      <div class="flex items-center gap-3">
        <div
          v-if="selectedDoctor.avatar_url"
          class="size-12 rounded-full overflow-hidden bg-gray-100 flex-shrink-0"
        >
          <img
            :src="selectedDoctor.avatar_url"
            :alt="selectedDoctor.full_name"
            class="w-full h-full object-cover"
          />
        </div>
        <div>
          <p class="font-medium text-heading">{{ selectedDoctor.full_name }}</p>
          <p v-if="selectedDoctor.specialization" class="text-sm text-subdued">
            {{ selectedDoctor.specialization }}
          </p>
        </div>
      </div>
    </div>

    <!-- Calendar -->
    <div class="mb-6">
      <v-date-picker
        v-model="internalDate"
        :min-date="minDate"
        :max-date="maxDate"
        :disabled-dates="disabledDates"
        :attributes="attributes"
        color="orange"
        is-expanded
        @input="handleDateChange"
      />
    </div>

    <!-- Next Button -->
    <div class="mt-8">
      <button
        @click="handleNext"
        :disabled="!internalDate"
        class="w-full p-3 lg:px-6 font-semibold rounded text-white transition-colors shadow-md"
        :class="{
          'bg-interactive hover:bg-interactive-button-hovered active:bg-interactive-button-hovered': internalDate,
          'bg-disabled text-disabled cursor-not-allowed': !internalDate,
        }"
      >
        Продолжить
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DateSelectionStep',

  props: {
    selectedDoctor: {
      type: Object,
      default: null,
    },
    selectedDate: {
      type: Date,
      default: null,
    },
    availableDates: {
      type: Array,
      default: () => [],
    },
  },

  data() {
    return {
      internalDate: this.selectedDate || new Date(),
      minDate: new Date(),
      maxDate: null,
    };
  },

  computed: {
    disabledDates() {
      // Если есть список доступных дат, отключаем все остальные
      if (this.availableDates.length > 0) {
        return {
          customPredictor: (date) => {
            const dateStr = this.formatDate(date);
            return !this.availableDates.includes(dateStr);
          },
        };
      }
      return null;
    },

    attributes() {
      // Подсветка доступных дат
      if (this.availableDates.length > 0) {
        return [
          {
            key: 'available',
            dot: {
              color: 'orange',
            },
            dates: this.availableDates.map((d) => new Date(d)),
          },
        ];
      }
      return [];
    },
  },

  mounted() {
    // Устанавливаем максимальную дату (например, +3 месяца)
    const max = new Date();
    max.setMonth(max.getMonth() + 3);
    this.maxDate = max;
  },

  methods: {
    handleDateChange(date) {
      this.$emit('select', date);
    },

    handleNext() {
      if (this.internalDate) {
        this.$emit('next');
      }
    },

    formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    },
  },

  watch: {
    selectedDate(newVal) {
      if (newVal) {
        this.internalDate = newVal;
      }
    },
  },
};
</script>
