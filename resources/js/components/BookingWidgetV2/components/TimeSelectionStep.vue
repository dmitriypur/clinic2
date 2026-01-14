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
        Выберите время приема
      </h2>
      <div class="w-16"></div>
    </div>

    <!-- Selected Info -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200 space-y-2">
      <div class="flex items-center gap-2">
        <svg class="size-5 text-interactive" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
            clip-rule="evenodd"
          />
        </svg>
        <p class="font-medium text-heading">{{ selectedDoctor?.full_name }}</p>
      </div>
      <div class="flex items-center gap-2">
        <svg class="size-5 text-interactive" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
            clip-rule="evenodd"
          />
        </svg>
        <p class="text-subdued">{{ formattedDate }}</p>
      </div>
    </div>

    <!-- Loader -->
    <div v-if="loading" class="flex justify-center items-center min-h-40">
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

    <!-- Time Slots -->
    <div v-else-if="slots.length > 0" class="space-y-4">
      <!-- Group by clinic/branch if needed -->
      <div v-for="group in groupedSlots" :key="group.key" class="space-y-3">
        <div v-if="group.clinicName" class="text-sm font-medium text-subdued">
          {{ group.clinicName }}
          <span v-if="group.branchName"> — {{ group.branchName }}</span>
        </div>

        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
          <button
            v-for="slot in group.slots"
            :key="slot.id"
            @click="selectSlot(slot)"
            :disabled="!slot.is_available || slot.is_occupied || slot.is_past"
            class="p-2 rounded-lg border text-sm font-medium transition-all"
            :class="getSlotClasses(slot)"
          >
            {{ slot.time }}
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <svg
        class="size-16 mx-auto mb-4 text-subdued"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
      <p class="text-subdued text-lg">
        На выбранную дату нет доступных слотов
      </p>
      <button
        @click="$emit('back')"
        class="mt-4 text-interactive hover:underline"
      >
        Выбрать другую дату
      </button>
    </div>

    <!-- Next Button -->
    <div v-if="slots.length > 0" class="mt-8">
      <button
        @click="handleNext"
        :disabled="!selectedSlot"
        class="w-full p-3 lg:px-6 font-semibold rounded text-white transition-colors shadow-md"
        :class="{
          'bg-interactive hover:bg-interactive-button-hovered active:bg-interactive-button-hovered': selectedSlot,
          'bg-disabled text-disabled cursor-not-allowed': !selectedSlot,
        }"
      >
        Продолжить
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TimeSelectionStep',

  props: {
    selectedDoctor: {
      type: Object,
      default: null,
    },
    selectedDate: {
      type: Date,
      default: null,
    },
    slots: {
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
    selectedSlot: {
      type: Object,
      default: null,
    },
  },

  computed: {
    formattedDate() {
      if (!this.selectedDate) return '';
      
      const options = { day: 'numeric', month: 'long', year: 'numeric' };
      return this.selectedDate.toLocaleDateString('ru-RU', options);
    },

    groupedSlots() {
      // Группируем слоты по клинике/филиалу
      const groups = {};
      
      this.slots.forEach((slot) => {
        const key = `${slot.clinic_id}-${slot.branch_id || 'default'}`;
        
        if (!groups[key]) {
          groups[key] = {
            key,
            clinicName: slot.clinic_name,
            branchName: slot.branch_name,
            slots: [],
          };
        }
        
        groups[key].slots.push(slot);
      });

      // Сортируем слоты внутри группы по времени
      Object.values(groups).forEach((group) => {
        group.slots.sort((a, b) => a.time.localeCompare(b.time));
      });

      return Object.values(groups);
    },
  },

  methods: {
    selectSlot(slot) {
      if (slot.is_available && !slot.is_occupied && !slot.is_past) {
        this.$emit('select', slot);
      }
    },

    handleNext() {
      if (this.selectedSlot) {
        this.$emit('next');
      }
    },

    getSlotClasses(slot) {
      if (!slot.is_available || slot.is_occupied || slot.is_past) {
        return 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed';
      }

      if (this.selectedSlot?.id === slot.id) {
        return 'border-interactive bg-interactive text-white hover:bg-interactive-button-hovered';
      }

      return 'border-gray-300 bg-white text-heading hover:border-interactive hover:bg-interactive/5';
    },
  },
};
</script>
