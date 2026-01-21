<template>
  <div class="p-6 md:p-8">
    <h2 class="text-xl md:text-2xl font-semibold text-heading mb-6">
      Выберите врача
    </h2>

    <!-- Loader -->
    <div v-if="loading" class="flex justify-center items-center min-h-60">
      <svg
        class="size-8 animate-spin text-action-primary"
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
    </div>

    <!-- Error -->
    <div v-else-if="error" class="text-center py-8">
      <p class="text-critical mb-4">{{ error }}</p>
      <button
        @click="$emit('retry')"
        class="px-4 py-2 bg-interactive text-white rounded-lg hover:bg-interactive-button-hovered"
      >
        Попробовать снова
      </button>
    </div>

    <!-- Doctor List -->
    <div v-else-if="doctors.length > 0" class="space-y-4">
      <div
        v-for="doctor in doctors"
        :key="doctor.id"
        @click="selectDoctor(doctor)"
        class="border rounded-lg p-4 cursor-pointer transition-all hover:border-interactive hover:shadow-md"
        :class="{
          'border-interactive bg-interactive/5': selectedDoctorId === doctor.id,
          'border-gray-200': selectedDoctorId !== doctor.id,
        }"
      >
        <div class="flex items-start gap-4">
          <!-- Avatar -->
          <div class="flex-shrink-0">
            <div
              v-if="doctor.photo_src"
              class="size-16 md:size-20 rounded-full overflow-hidden bg-gray-100"
            >
              <img
                :src="doctor.photo_src"
                :alt="doctor.name"
                class="w-full h-full object-cover"
              />
            </div>
            <div
              v-else
              class="size-16 md:size-20 rounded-full bg-gradient-to-br from-interactive/20 to-interactive/10 flex items-center justify-center"
            >
              <svg
                class="size-8 text-interactive"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <h3 class="text-lg font-semibold text-heading mb-1">
              {{ doctor.name }}
            </h3>
            <p v-if="doctor.specialization" class="text-sm text-subdued mb-2">
              {{ doctor.specialization }}
            </p>
            <div v-if="doctor.experience" class="text-sm text-subdued">
              Стаж: {{ doctor.experience }}
              {{ pluralizeYears(doctor.experience) }}
            </div>
          </div>

          <!-- Checkbox -->
          <div class="flex-shrink-0">
            <div
              class="size-6 rounded-full border-2 flex items-center justify-center transition-colors"
              :class="{
                'border-interactive bg-interactive':
                  selectedDoctorId === doctor.id,
                'border-gray-300': selectedDoctorId !== doctor.id,
              }"
            >
              <svg
                v-if="selectedDoctorId === doctor.id"
                class="size-4 text-white"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <p class="text-subdued text-lg">К сожалению, врачи не найдены</p>
    </div>

    <!-- Next Button -->
    <div v-if="doctors.length > 0" class="mt-8">
      <button
        @click="handleNext"
        :disabled="!selectedDoctorId"
        class="w-full p-3 lg:px-6 font-semibold rounded text-white transition-colors shadow-md"
        :class="{
          'bg-interactive hover:bg-interactive-button-hovered active:bg-interactive-button-hovered':
            selectedDoctorId,
          'bg-disabled text-disabled cursor-not-allowed': !selectedDoctorId,
        }"
      >
        Продолжить
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: "DoctorSelectionStep",

  props: {
    doctors: {
      type: Array,
      default: () => [],
    },
    loading: {
      type: Boolean,
      default: false,
    },
    error: {
      type: String,
      default: null,
    },
    selectedDoctorId: {
      type: [String, Number],
      default: null,
    },
  },

  methods: {
    selectDoctor(doctor) {
      this.$emit("select", doctor);
    },

    handleNext() {
      if (this.selectedDoctorId) {
        this.$emit("next");
      }
    },

    pluralizeYears(count) {
      const cases = [2, 0, 1, 1, 1, 2];
      const titles = ["год", "года", "лет"];
      return titles[
        count % 100 > 4 && count % 100 < 20
          ? 2
          : cases[count % 10 < 5 ? count % 10 : 5]
      ];
    },
  },
};
</script>
